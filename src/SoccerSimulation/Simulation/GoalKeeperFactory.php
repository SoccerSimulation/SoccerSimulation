<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Simulation\GoalKeeperStates\TendGoal;

class GoalKeeperFactory
{
    /**
     * @param SoccerTeam $team
     * @param int $homeRegion
     * @return FieldPlayer
     */
    public function create(SoccerTeam $team, $homeRegion)
    {
        return new GoalKeeper(
            $team,
            $homeRegion,
            TendGoal::getInstance(),
            new Vector2D(0, -1),
            new Vector2D(0.0, 0.0),
            Prm::PlayerMass,
            Prm::PlayerMaxForce,
            Prm::PlayerMaxSpeedWithoutBall,
            Prm::PlayerMaxTurnRate,
            Prm::PlayerScale);
    }
}
