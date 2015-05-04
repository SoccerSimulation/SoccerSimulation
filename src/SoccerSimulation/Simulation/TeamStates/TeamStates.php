<?php

namespace SoccerSimulation\Simulation\TeamStates;

use SoccerSimulation\Simulation\SoccerTeam;

/**
 * Desc: State prototypes for soccer team states
 */
class TeamStates
{

    public static function changePlayerHomeRegions(SoccerTeam $team, array $NewRegions)
    {
        $index = 0;
        foreach ($team->getPlayers() as $player) {
            $player->setHomeRegion($NewRegions[$index++]);
        }
    }
}
