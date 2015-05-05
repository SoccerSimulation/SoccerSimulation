<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\EnterStateEvent;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Simulation\FieldPlayer;

class Wait extends State
{
    /**
     * @var Wait
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Wait();
        }

        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player)
    {
        $this->raise(new EnterStateEvent($this, $player));

        //if the game is not on make sure the target is the center of the player's
        //home region. This is ensure all the players are in the correct positions
        //ready for kick off
        if (!$player->getPitch()->isGameActive()) {
            $player->getSteering()->setTarget($player->getHomeRegion()->getCenter());
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player)
    {
        //if the player has been jostled out of position, get back in position
        if (!$player->isAtTarget()) {
            $player->getSteering()->activateArrive();

            return;
        } else {
            $player->getSteering()->deactivateArrive();

            $player->setVelocity(new Vector2D(0, 0));

            //the player should keep his eyes on the ball!
            $player->trackBall();
        }

        //if this player's team is controlling AND this player is not the attacker
        //AND is further up the field than the attacker he should request a pass.
        if ($player->getTeam()->isInControl()
            && (!$player->isControllingPlayer())
            && $player->isAheadOfAttacker()
        ) {
            $player->getTeam()->requestPass($player);

            return;
        }

        if ($player->getPitch()->isGameActive()) {
            //if the ball is nearer this player than any other team member  AND
            //there is not an assigned receiver AND neither goalkeeper has
            //the ball, go chase it
            if ($player->isClosestTeamMemberToBall()
                && $player->getTeam()->getReceiver() == null
                && !$player->getPitch()->hasGoalKeeperBall()
            ) {
                $player->getStateMachine()->changeState(ChaseBall::getInstance());

                return;
            }
        }
    }
}
