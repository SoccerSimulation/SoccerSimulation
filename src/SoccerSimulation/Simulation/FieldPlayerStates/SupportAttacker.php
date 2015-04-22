<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\Define;
use SoccerSimulation\Simulation\FieldPlayer;
use SoccerSimulation\Simulation\Prm;

class SupportAttacker extends State
{
    /**
     * @var SupportAttacker
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new SupportAttacker();
        }
        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player) {
        $player->getSteering()->activateArrive();

        $player->getSteering()->setTarget($player->getTeam()->getSupportSpot());

        if (Define::PLAYER_STATE_INFO_ON) {
            $player->addDebugMessages('Player ' . $player->getId() . ' enters support state');
            echo "Player " . $player->getId() . " enters support state\n";
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player) {
        //if his team loses control go back home
        if (!$player->getTeam()->isInControl()) {
            $player->getStateMachine()->changeState(ReturnToHomeRegion::getInstance());
            return;
        }

        //if the best supporting spot changes, change the steering target
        if ($player->getTeam()->getSupportSpot() != $player->getSteering()->getTarget()) {
            $player->getSteering()->setTarget($player->getTeam()->getSupportSpot());

            $player->getSteering()->activateArrive();
        }

        //if this player has a shot at the goal AND the attacker can pass
        //the ball to him the attacker should pass the ball to this player
        if ($player->getTeam()->canShoot($player->getPosition(),
                Prm::MaxShootingForce)) {
            $player->getTeam()->requestPass($player);
        }


        //if this player is located at the support spot and his team still have
        //possession, he should remain still and turn to face the ball
        if ($player->isAtTarget()) {
            $player->getSteering()->deactivateArrive();

            //the player should keep his eyes on the ball!
            $player->trackBall();

            $player->setVelocity(new Vector2D(0, 0));

            //if not threatened by another player request a pass
            if (!$player->isThreatened()) {
                $player->getTeam()->requestPass($player);
            }
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function quit($player) {
        //set supporting player to null so that the team knows it has to 
        //determine a new one.
        $player->getTeam()->resetSupportingPlayer();

        $player->getSteering()->deactivateArrive();
    }

    /**
     * @param FieldPlayer $e
     * @param Telegram $t
     *
     * @return bool
     */
    public function onMessage($e, Telegram $t) {
        return false;
    }
}
