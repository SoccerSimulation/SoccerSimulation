<?php

namespace SoccerSimulation\Simulation;

class GoalKeeperFactory extends PlayerBaseFactory
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
            $this->getMass(),
            $this->getMaxForce(),
            $this->getMaxSpeedWithBall(),
            $this->getMaxSpeedWithoutBall()
        );
    }
}
