<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Simulation\FieldPlayerStates\Wait;

class FieldPlayerFactory
{
    /**
     * @param SoccerTeam $team
     * @param int $homeRegion
     * @param string $role
     *
     * @return FieldPlayer
     */
    public function create(SoccerTeam $team, $homeRegion, $role)
    {
        return new FieldPlayer(
            $team,
            $homeRegion,
            Wait::getInstance(),
            new Vector2D(0, -1),
            new Vector2D(0, 0),
            Prm::PlayerMass,
            Prm::PlayerMaxForce,
            Prm::PlayerMaxSpeedWithoutBall,
            Prm::PlayerMaxTurnRate,
            Prm::PlayerScale,
            $role);
    }
}
