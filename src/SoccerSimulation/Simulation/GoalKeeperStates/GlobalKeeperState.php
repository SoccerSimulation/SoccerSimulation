<?php

namespace SoccerSimulation\Simulation\GoalKeeperStates;

use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\GoalKeeper;
use SoccerSimulation\Simulation\MessageTypes;

class GlobalKeeperState extends State
{

    /**
     * @var GlobalKeeperState
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new GlobalKeeperState();
        }

        return self::$instance;
    }

    /**
     * @param GoalKeeper $keeper
     * @param Telegram $telegram
     *
     * @return bool
     */
    public function onMessage($keeper, Telegram $telegram)
    {
        switch ($telegram->message->messageType) {
            case MessageTypes::Msg_GoHome:
                $keeper->setDefaultHomeRegion();
                $keeper->getStateMachine()->changeState(ReturnHome::getInstance());
                return true;

            case MessageTypes::Msg_ReceiveBall:
                $keeper->getStateMachine()->changeState(InterceptBall::getInstance());
                return true;

        }

        return false;
    }
}
