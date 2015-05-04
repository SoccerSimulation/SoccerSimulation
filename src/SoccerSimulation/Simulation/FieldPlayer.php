<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Transformation;
use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\FSM\StateMachine;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Common\Time\Regulator;
use SoccerSimulation\Simulation\FieldPlayerStates\GlobalPlayerState;
use SoccerSimulation\Simulation\FieldPlayerStates\Wait;

/**
 *   Desc:   Derived from a PlayerBase, this class encapsulates a player
 *           capable of moving around a soccer pitch, kicking, dribbling,
 *           shooting etc
 */
class FieldPlayer extends PlayerBase implements \JsonSerializable
{
    const PLAYER_ROLE_DEFENDER = 'defender';
    const PLAYER_ROLE_ATTACKER = 'attacker';

    /**
     * @var Regulator
     *
     * limits the number of kicks a player may take per second
     */
    private $kickLimiter;

    /**
     * @var string
     */
    private $role;

    /**
     * @param SoccerTeam $homeTeam
     * @param int $homeRegion
     * @param float $mass
     * @param float $maxForce
     * @param float $maxSpeedWithBall
     * @param float $maxSpeedWithoutBall
     * @param string $role
     */
    public function __construct(
        SoccerTeam $homeTeam,
        $homeRegion,
        $mass,
        $maxForce,
        $maxSpeedWithBall,
        $maxSpeedWithoutBall,
        $role
    ) {
        parent::__construct($homeTeam,
            $homeRegion,
            $mass,
            $maxForce,
            $maxSpeedWithBall,
            $maxSpeedWithoutBall);

        $this->role = $role;

        //set up the state machine
        $this->stateMachine = new StateMachine($this, Wait::getInstance(), Wait::getInstance(), GlobalPlayerState::getInstance());
        $this->stateMachine->getCurrentState()->enter($this);

        //set up the kick regulator
        $this->kickLimiter = new Regulator(Prm::PlayerKickFrequency);
    }


    /**
     * call this to update the player's position and orientation
     */
    public function update()
    {
        parent::update();

        //run the logic for the current state
        $this->stateMachine->update();
        $this->raiseMultiple($this->stateMachine->releaseEvents());

        //calculate the combined steering force
        $this->steering->calculate();

        //if no steering force is produced decelerate the player by applying a
        //braking force
        if ($this->steering->getForce()->isZero()) {
            $brakingRate = 0.8;

            $this->velocity->mul($brakingRate);
        }

        //the steering force's side component is a force that rotates the 
        //player about its axis. We must limit the rotation so that a player
        //can only turn by PlayerMaxTurnRate rads per update.
        $turningForce = $this->steering->getSideComponent();
        $turningForce = max(min(Prm::PlayerMaxTurnRate, $turningForce), -Prm::PlayerMaxTurnRate);

        //rotate the heading vector
        Transformation::vectorRotateAroundOrigin($this->heading, $turningForce);

        //make sure the velocity vector points in the same direction as
        //the heading vector
        $this->velocity = Vector2D::staticMul($this->heading, $this->velocity->getLength());

        //now to calculate the acceleration due to the force exerted by
        //the forward component of the steering force in the direction
        //of the player's heading
        $accel = Vector2D::staticMul($this->heading, $this->steering->getForwardComponent() / $this->mass);

        $this->velocity->add($accel);

        //make sure player does not exceed maximum velocity
        $this->velocity->truncate($this->getMaxSpeed());
        //update the position
        $this->position->add($this->velocity);
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
    public function isReadyForNextKick()
    {
        return $this->kickLimiter->isReady();
    }

    /**
     * @return bool
     */
    public function isGoalkeeper()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }
}
