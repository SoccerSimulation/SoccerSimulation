<?php

namespace SoccerSimulation\Simulation\TeamStates;

use SoccerSimulation\Common\FSM\EnterStateEvent;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Simulation\SoccerTeam;

class Defending extends State
{
    /**
     * @var Defending
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Defending();
        }

        return self::$instance;
    }

    /**
     * @param SoccerTeam $team
     */
    public function enter($team)
    {
        $this->raise(new EnterStateEvent($this, $team));

        //these define the home regions for this state of each of the players
        $blueRegions = array(3, 8, 9, 11, 12, 24, 22, 26, 31, 37, 39);
        $redRegions = array(80, 75, 74, 72, 71, 59, 61, 57, 52, 44, 46);

        //set up the player's home regions
        if ($team->getColor() == SoccerTeam::COLOR_BLUE) {
            TeamStates::changePlayerHomeRegions($team, $blueRegions);
        } else {
            TeamStates::changePlayerHomeRegions($team, $redRegions);
        }

        //if a player is in either the Wait or ReturnToHomeRegion states, its
        //steering target must be updated to that of its new home region
        $team->updateTargetsOfWaitingPlayers();
    }

    /**
     * @param SoccerTeam $team
     */
    public function execute($team)
    {
        //if in control change states
        if ($team->isInControl()) {
            $team->getStateMachine()->changeState(Attacking::getInstance());

            return;
        }
    }
}
