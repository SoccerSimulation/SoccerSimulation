<?php

namespace SoccerSimulation\Simulation\GoalKeeperStates;

use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\GoalKeeper;

/**
 *
//------------------------- ReturnHome: ----------------------------------
//
//  In this state the goalkeeper simply returns back to the center of
//  the goal region before changing state back to TendGoal
//------------------------------------------------------------------------
 */
class ReturnHome extends State
{
    /**
     * @var ReturnHome
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ReturnHome();
        }
        return self::$instance;
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function enter($keeper) {
        $keeper->getSteering()->activateArrive();
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function execute($keeper) {
        $keeper->getSteering()->setTarget($keeper->getHomeRegion()->getCenter());

        //if close enough to home or the opponents get control over the ball,
        //change state to tend goal
        if ($keeper->isInHomeRegion() || !$keeper->getTeam()->isInControl()) {
            $keeper->getStateMachine()->changeState(TendGoal::getInstance());
        }
    }

    /**
     * @param GoalKeeper $keeper
     */
    public function quit($keeper) {
        $keeper->getSteering()->deactivateArrive();
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