<?php

namespace SoccerSimulation\Simulation\GoalKeeperStates;

use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\Define;
use SoccerSimulation\Simulation\GoalKeeper;

/**
 *  In this state the GP will attempt to intercept the ball using the
 *  pursuit steering behavior, but he only does so so long as he remains
 *  within his home region.
 */
class InterceptBall extends State
{
    /**
     * @var InterceptBall
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new InterceptBall();
        }
        return self::$instance;
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function enter($keeper) {
        $keeper->getSteering()->activatePursuit();

        if (Define::GOALY_STATE_INFO_ON) {
            $keeper->addDebugMessages('Goaly ' . $keeper->getId() . ' enters InterceptBall state');
            echo "Goaly " . $keeper->getId() . " enters InterceptBall state\n";
        }
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function execute($keeper) {
        //if the goalkeeper moves to far away from the goal he should return to his
        //home region UNLESS he is the closest player to the ball, in which case,
        //he should keep trying to intercept it.
        if ($keeper->isTooFarFromGoalMouth() && !$keeper->isClosestPlayerOnPitchToBall()) {
            $keeper->getStateMachine()->changeState(ReturnHome::getInstance());
            return;
        }

        //if the ball becomes in range of the goalkeeper's hands he traps the 
        //ball and puts it back in play
        if ($keeper->isBallWithinKeeperRange()) {
            $keeper->getBall()->trap();

            $keeper->getPitch()->setGoalKeeperHasBall(true);

            $keeper->getStateMachine()->changeState(PutBallBackInPlay::getInstance());

            return;
        }
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function quit($keeper) {
        $keeper->getSteering()->deactivatePursuit();
    }

    /**
     * @param GoalKeeper $e
     * @param Telegram $t
     *
     * @return bool
     */
    public function onMessage($e, Telegram $t) {
        return false;
    }
}
