<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\Define;
use SoccerSimulation\Simulation\FieldPlayer;

class ChaseBall extends State
{
    /**
     * @var ChaseBall
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
            self::$instance = new ChaseBall();
        }

        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player)
    {
        $player->getSteering()->activateSeek();

        if (Define::PLAYER_STATE_INFO_ON)
        {
            echo "Player " . $player->getId() . " enters chase state\n";
            $player->addDebugMessages('Player ' . $player->getId() . ' enters chase state');
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player)
    {
        //if the ball is within kicking range the player changes state to KickBall.
        if ($player->isBallWithinKickingRange())
        {
            $player->getStateMachine()->changeState(KickBall::getInstance());

            return;
        }

        //if the player is the closest player to the ball then he should keep
        //chasing it
        if ($player->isClosestTeamMemberToBall())
        {
            $player->getSteering()->setTarget($player->getBall()->getPosition());

            return;
        }

        //if the player is not closest to the ball anymore, he should return back
        //to his home region and wait for another opportunity
        $player->getStateMachine()->changeState(ReturnToHomeRegion::getInstance());
    }

    /**
     * @param FieldPlayer $player
     */
    public function quit($player)
    {
        $player->getSteering()->deactivateSeek();
    }

    /**
     * @param FieldPlayer $e
     * @param Telegram $t
     *
     * @return bool
     */
    public function onMessage($e, Telegram $t)
    {
        return false;
    }
}
