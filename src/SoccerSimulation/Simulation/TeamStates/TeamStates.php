<?php

namespace SoccerSimulation\Simulation\TeamStates;

use SoccerSimulation\Simulation\SoccerTeam;

/**
 * Desc: State prototypes for soccer team states
 */
class TeamStates {

    public static function changePlayerHomeRegions(SoccerTeam $team, array $NewRegions) {
        for ($plyr = 0; $plyr < count($NewRegions); ++$plyr) {
            $team->setPlayerHomeRegion($plyr, $NewRegions[$plyr]);
        }
    }
}
