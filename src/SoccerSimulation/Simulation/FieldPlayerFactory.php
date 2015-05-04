<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Simulation\FieldPlayerStates\Wait;

class FieldPlayerFactory extends PlayerBaseFactory
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
            $this->getMass(),
            $this->getMaxForce(),
            $this->getMaxSpeedWithBall(),
            $this->getMaxSpeedWithoutBall(),
            $role
        );
    }

    /**
     * @param SoccerTeam $team
     *
     * @return FieldPlayer[]
     */
    public function createCompleteLineUp(SoccerTeam $team)
    {
        $players = [];
        if ($team->Color() == SoccerTeam::COLOR_RED) {
            $players[] = $this->create($team, 75, PlayerBase::PLAYER_ROLE_DEFENDER); // LV
            $players[] = $this->create($team, 74, PlayerBase::PLAYER_ROLE_DEFENDER); // RV
            $players[] = $this->create($team, 72, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 71, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 59, PlayerBase::PLAYER_ROLE_DEFENDER); // DM
            $players[] = $this->create($team, 61, PlayerBase::PLAYER_ROLE_ATTACKER); // LM
            $players[] = $this->create($team, 57, PlayerBase::PLAYER_ROLE_ATTACKER); // RM
            $players[] = $this->create($team, 52, PlayerBase::PLAYER_ROLE_ATTACKER); // OM
            $players[] = $this->create($team, 44, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
            $players[] = $this->create($team, 46, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
        } else {
            $players[] = $this->create($team, 8, PlayerBase::PLAYER_ROLE_DEFENDER); // LV
            $players[] = $this->create($team, 9, PlayerBase::PLAYER_ROLE_DEFENDER); // RV
            $players[] = $this->create($team, 11, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 12, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 24, PlayerBase::PLAYER_ROLE_DEFENDER); // DM
            $players[] = $this->create($team, 22, PlayerBase::PLAYER_ROLE_ATTACKER); // LM
            $players[] = $this->create($team, 26, PlayerBase::PLAYER_ROLE_ATTACKER); // RM
            $players[] = $this->create($team, 31, PlayerBase::PLAYER_ROLE_ATTACKER); // OM
            $players[] = $this->create($team, 37, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
            $players[] = $this->create($team, 39, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
        }

        return $players;
    }
}
