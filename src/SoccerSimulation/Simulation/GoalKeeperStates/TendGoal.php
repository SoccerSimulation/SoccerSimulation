<?php

namespace SoccerSimulation\Simulation\GoalKeeperStates;

use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\GoalKeeper;
use SoccerSimulation\Simulation\Prm;

/**
 *
 *  This is the main state for the goalkeeper. When in this state he will
 *  move left to right across the goalmouth using the 'interpose' steering
 *  behavior to put himself between the ball and the back of the net.
 *
 *  If the ball comes within the 'goalkeeper range' he moves out of the
 *  goalmouth to attempt to intercept it. (see next state)
 */
class TendGoal extends State
{
    /**
     * @var TendGoal
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new TendGoal();
        }

        return self::$instance;
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function enter($keeper)
    {
        //turn interpose on
        $keeper->getSteering()->activateInterpose(Prm::GoalKeeperTendingDistance);

        //interpose will position the agent between the ball position and a target
        //position situated along the goal mouth. This call sets the target
        $keeper->getSteering()->setTarget($keeper->getRearInterposeTarget());
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function execute($keeper)
    {
        //the rear interpose target will change as the ball's position changes
        //so it must be updated each update-step 
        $keeper->getSteering()->setTarget($keeper->getRearInterposeTarget());

        //if the ball comes in range the keeper traps it and then changes state
        //to put the ball back in play
        if ($keeper->isBallWithinKeeperRange()) {
            $keeper->getBall()->trap();

            $keeper->getPitch()->setGoalKeeperHasBall(true);

            $keeper->getStateMachine()->changeState(PutBallBackInPlay::getInstance());

            return;
        }

        //if ball is within a predefined distance, the keeper moves out from
        //position to try and intercept it.
        if ($keeper->isBallWithinRangeForIntercept() && !$keeper->getTeam()->isInControl()) {
            $keeper->getStateMachine()->changeState(InterceptBall::getInstance());
        }

        //if the keeper has ventured too far away from the goal-line and there
        //is no threat from the opponents he should move back towards it
        if ($keeper->isTooFarFromGoalMouth() && $keeper->getTeam()->isInControl()) {
            $keeper->getStateMachine()->changeState(ReturnHome::getInstance());

            return;
        }
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function quit($keeper)
    {
        $keeper->getSteering()->deactivateInterpose();
    }

    public function onMessage($e, Telegram $t)
    {
        return false;
    }
}
