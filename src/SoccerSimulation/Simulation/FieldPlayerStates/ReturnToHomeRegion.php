<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Game\Region;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\Define;
use SoccerSimulation\Simulation\FieldPlayer;

class ReturnToHomeRegion extends State
{
    /**
     * @var ReturnToHomeRegion
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new ReturnToHomeRegion();
        }
        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player) {
        $player->getSteering()->activateArrive();

        if (!$player->getHomeRegion()->isInside($player->getSteering()->getTarget(), Region::REGION_MODIFIER_HALFSIZE)) {
            $player->getSteering()->setTarget($player->getHomeRegion()->getCenter());
        }

        if (Define::PLAYER_STATE_INFO_ON) {
            $player->addDebugMessages('Player ' . $player->getId() . ' enters ReturnToHome state');
            echo "Player " . $player->getId() . " enters ReturnToHome state\n";
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player) {
        if ($player->getPitch()->isGameActive()) {
            //if the ball is nearer this player than any other team member  &&
            //there is not an assigned receiver && the goalkeeper does not gave
            //the ball, go chase it
            if ($player->isClosestTeamMemberToBall()
                    && ($player->getTeam()->getReceiver() == null)
                    && !$player->getPitch()->hasGoalKeeperBall()) {
                $player->getStateMachine()->changeState(ChaseBall::getInstance());

                return;
            }
        }

        //if game is on and close enough to home, change state to wait and set the 
        //player target to his current position.(so that if he gets jostled out of 
        //position he can move back to it)
        if ($player->getPitch()->isGameActive() && $player->getHomeRegion()->isInside($player->getPosition(),
                Region::REGION_MODIFIER_HALFSIZE)) {
            $player->getSteering()->setTarget($player->getPosition());
            $player->getStateMachine()->changeState(Wait::getInstance());
        } //if game is not on the player must return much closer to the center of his
        //home region
        else if (!$player->getPitch()->isGameActive() && $player->isAtTarget()) {
            $player->getStateMachine()->changeState(Wait::getInstance());
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function quit($player) {
        $player->getSteering()->deactivateArrive();
    }

    public function onMessage($e, Telegram $t) {
        return false;
    }
}
