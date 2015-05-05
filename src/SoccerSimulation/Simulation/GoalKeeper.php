<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\StateMachine;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\GoalKeeperStates\GlobalKeeperState;
use SoccerSimulation\Simulation\GoalKeeperStates\TendGoal;

/**
 * Desc:   class to implement a goalkeeper agent
 */
class GoalKeeper extends PlayerBase implements \JsonSerializable
{
    /**
     * @var Vector2D
     *
     * this vector is updated to point towards the ball and is used when
     * rendering the goalkeeper (instead of the underlaying vehicle's heading)
     * to ensure he always appears to be watching the ball
     */
    private $lookAt;

    public function __construct(
        SoccerTeam $homeTeam,
        $homeRegion,
        $mass,
        $maxForce,
        $maxSpeedWithBall,
        $maxSpeedWithoutBall
    ) {
        parent::__construct($homeTeam,
            $homeRegion,
            $mass,
            $maxForce,
            $maxSpeedWithBall,
            $maxSpeedWithoutBall);

        $this->lookAt = new Vector2D();

        //set up the state machine
        $this->stateMachine = new StateMachine($this, TendGoal::getInstance(), TendGoal::getInstance(), GlobalKeeperState::getInstance());
        $this->stateMachine->getCurrentState()->enter($this);
    }

    //these must be implemented
    public function update()
    {
        parent::update();

        //run the logic for the current state
        $this->stateMachine->update();
        $this->raiseMultiple($this->stateMachine->releaseEvents());

        //calculate the combined force from each steering behavior 
        $SteeringForce = $this->steering->calculate();

        //Acceleration = Force/Mass
        $Acceleration = Vector2D::staticDiv($SteeringForce, $this->mass);
        //update velocity
        $this->velocity->add($Acceleration);

        //make sure player does not exceed maximum velocity
        $this->velocity->truncate($this->getMaxSpeed());

        //update the position
        $this->position->add($this->velocity);

        //update the heading if the player has a non zero velocity
        if (!$this->velocity->isZero()) {
            $this->heading = Vector2D::vectorNormalize($this->velocity);
        }

        //look-at vector always points toward the ball
        if (!$this->getPitch()->hasGoalKeeperBall()) {
            $this->lookAt = Vector2D::vectorNormalize(Vector2D::staticSub($this->getBall()->getPosition(),
                $this->getPosition()));
        }
    }

    /**
     * routes any messages appropriately
     *
     * @param Telegram $message
     *
     * @return bool
     */
    public function handleMessage(Telegram $message)
    {
        return $this->stateMachine->handleMessage($message);
    }

    /**
     * @return bool
     */
    public function isBallWithinRangeForIntercept()
    {
        return $this->getTeam()->getHomeGoal()->getCenter()->distanceTo($this->getBall()->getPosition()) <= Prm::GoalKeeperInterceptRange;
    }

    /**
     * @return bool
     */
    public function isTooFarFromGoalMouth()
    {
        return $this->distanceTo($this->getRearInterposeTarget()) > Prm::GoalKeeperInterceptRange;
    }

    /**
     * this method is called by the Intercept state to determine the spot
     * along the goalmouth which will act as one of the interpose targets
     * (the other is the ball).
     * the specific point at the goal line that the keeper is trying to cover
     * is flexible and can move depending on where the ball is on the field.
     * To achieve this we just scale the ball's y value by the ratio of the
     * goal width to playingfield width
     */
    public function getRearInterposeTarget()
    {
        $xPosTarget = $this->getTeam()->getHomeGoal()->getCenter()->x;

        $yPosTarget = $this->getPitch()->getPlayingArea()->getCenter()->y
            - Prm::GoalWidth * 0.5 + ($this->getBall()->getPosition()->y * Prm::GoalWidth)
            / $this->getPitch()->getPlayingArea()->getHeight();

        return new Vector2D($xPosTarget, $yPosTarget);
    }

    public function getStateMachine()
    {
        return $this->stateMachine;
    }

    /**
     * @return bool
     */
    public function isGoalkeeper()
    {
        return true;
    }
}
