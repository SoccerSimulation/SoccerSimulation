<?php

namespace SoccerSimulation\Simulation\TeamStates;

use SoccerSimulation\Common\FSM\EnterStateEvent;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Simulation\Define;
use SoccerSimulation\Simulation\SoccerTeam;

class Attacking extends State
{
    /**
     * @var Attacking
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Attacking();
        }

        return self::$instance;
    }

    /**
     * @param SoccerTeam $team
     */
    public function enter($team)
    {
        if (Define::DEBUG_TEAM_STATES) {
            $this->raise(new EnterStateEvent($this, $team));
        }

        //these define the home regions for this state of each of the players
        $blueRegions = array(3, 15, 16, 18, 19, 38, 49, 55, 59, 72, 74);
        $redRegions = array(80, 68, 67, 65, 64, 45, 34, 28, 24, 9, 11);

        //set up the player's home regions
        if ($team->getColor() == SoccerTeam::COLOR_BLUE) {
            TeamStates::changePlayerHomeRegions($team, $blueRegions);
        } else {
            TeamStates::changePlayerHomeRegions($team, $redRegions);
        }

        //if a player is in either the Wait or ReturnToHomeRegion states, its
        //steering target must be updated to that of its new home region to enable
        //it to move into the correct position.
        $team->updateTargetsOfWaitingPlayers();
    }

    /**
     * @param SoccerTeam $team
     */
    public function execute($team)
    {
        //if this team is no longer in control change states
        if (!$team->isInControl()) {
            $team->getStateMachine()->changeState(Defending::getInstance());

            return;
        }

        //calculate the best position for any supporting attacker to move to
        $team->determineBestSupportingPosition();
    }

    /**
     * @param SoccerTeam $team
     */
    public function quit($team)
    {
        //there is no supporting player for defense
        $team->resetSupportingPlayer();
    }
}
