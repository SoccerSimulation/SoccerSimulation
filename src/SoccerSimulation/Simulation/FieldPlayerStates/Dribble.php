<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Transformation;
use SoccerSimulation\Common\FSM\EnterStateEvent;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Simulation\FieldPlayer;
use SoccerSimulation\Simulation\Prm;

class Dribble extends State
{
    /**
     * @var Dribble
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Dribble();
        }

        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player)
    {
        //let the team know this player is controlling
        $player->getTeam()->setControllingPlayer($player);

        $this->raise(new EnterStateEvent($this, $player));
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player)
    {
        $dot = $player->getTeam()->getHomeGoal()->getFacing()->dot($player->getHeading());

        //if the ball is between the player and the home goal, it needs to swivel
        // the ball around by doing multiple small kicks and turns until the player 
        //is facing in the correct direction
        if ($dot < 0) {
            //the player's heading is going to be rotated by a small amount (Pi/4) 
            //and then the ball will be kicked in that direction
            $direction = $player->getHeading();

            //calculate the sign (+/-) of the angle between the player heading and the 
            //facing direction of the goal so that the player rotates around in the 
            //correct direction
            $angle = pi() / 4 * -1 * $player->getTeam()->getHomeGoal()->getFacing()->sign($player->getHeading());

            Transformation::vectorRotateAroundOrigin($direction, $angle);

            //this value works well whjen the player is attempting to control the
            //ball and turn at the same time
            $kickingForce = 0.8;

            $player->getBall()->kick($direction, $kickingForce);
        } //kick the ball down the field
        else {
            $player->getBall()->kick($player->getTeam()->getHomeGoal()->getFacing(), Prm::MaxDribbleForce);
        }

        //the player has kicked the ball so he must now change state to follow it
        $player->getStateMachine()->changeState(ChaseBall::getInstance());

        return;
    }
}
