<?php

namespace SoccerSimulation\Simulation;

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
        if ($team->getColor() == SoccerTeam::COLOR_RED) {
            $players[] = $this->create($team, 75, FieldPlayer::PLAYER_ROLE_DEFENDER); // LV
            $players[] = $this->create($team, 74, FieldPlayer::PLAYER_ROLE_DEFENDER); // RV
            $players[] = $this->create($team, 72, FieldPlayer::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 71, FieldPlayer::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 59, FieldPlayer::PLAYER_ROLE_DEFENDER); // DM
            $players[] = $this->create($team, 61, FieldPlayer::PLAYER_ROLE_ATTACKER); // LM
            $players[] = $this->create($team, 57, FieldPlayer::PLAYER_ROLE_ATTACKER); // RM
            $players[] = $this->create($team, 52, FieldPlayer::PLAYER_ROLE_ATTACKER); // OM
            $players[] = $this->create($team, 44, FieldPlayer::PLAYER_ROLE_ATTACKER); // MS
            $players[] = $this->create($team, 46, FieldPlayer::PLAYER_ROLE_ATTACKER); // MS
        } else {
            $players[] = $this->create($team, 8, FieldPlayer::PLAYER_ROLE_DEFENDER); // LV
            $players[] = $this->create($team, 9, FieldPlayer::PLAYER_ROLE_DEFENDER); // RV
            $players[] = $this->create($team, 11, FieldPlayer::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 12, FieldPlayer::PLAYER_ROLE_DEFENDER); // IV
            $players[] = $this->create($team, 24, FieldPlayer::PLAYER_ROLE_DEFENDER); // DM
            $players[] = $this->create($team, 22, FieldPlayer::PLAYER_ROLE_ATTACKER); // LM
            $players[] = $this->create($team, 26, FieldPlayer::PLAYER_ROLE_ATTACKER); // RM
            $players[] = $this->create($team, 31, FieldPlayer::PLAYER_ROLE_ATTACKER); // OM
            $players[] = $this->create($team, 37, FieldPlayer::PLAYER_ROLE_ATTACKER); // MS
            $players[] = $this->create($team, 39, FieldPlayer::PLAYER_ROLE_ATTACKER); // MS
        }

        return $players;
    }
}
