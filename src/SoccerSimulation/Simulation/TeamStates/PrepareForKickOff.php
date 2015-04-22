<?php

namespace SoccerSimulation\Simulation\TeamStates;

use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\SoccerTeam;

class PrepareForKickOff extends State
{
    /**
     * @var PrepareForKickOff
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null)
        {
            self::$instance = new PrepareForKickOff();
        }

        return self::$instance;
    }

    /**
     * @param SoccerTeam $team
     */
    public function enter($team)
    {
        $team->resetControllingPlayer();
        $team->resetSupportingPlayer();
        $team->resetReceiver();
        $team->resetPlayerClosestToBall();
        $team->returnAllFieldPlayersToHome();
    }

    /**
     * @param SoccerTeam $team
     */
    public function execute($team)
    {
        //if both teams in position, start the game
        if ($team->allPlayersAtHome() && $team->getOpponent()->allPlayersAtHome())
        {
            $team->getStateMachine()->changeState(Defending::getInstance());
        }
    }

    /**
     * @param SoccerTeam $team
     */
    public function quit($team)
    {
        $team->getPitch()->setGameIsActive();
    }

    /**
     * @param mixed $e
     * @param Telegram $t
     *
     * @return bool
     */
    public function onMessage($e, Telegram $t)
    {
        return false;
    }
}