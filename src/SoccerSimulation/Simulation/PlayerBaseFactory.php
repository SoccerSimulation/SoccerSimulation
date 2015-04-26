<?php

namespace SoccerSimulation\Simulation;

abstract class PlayerBaseFactory
{
    /**
     * @return float
     */
    protected function getMaxSpeedWithBall()
    {
        return $this->getRand(Prm::PlayerMaxSpeedWithBallMin, Prm::PlayerMaxSpeedWithBallMax);
    }

    /**
     * @return float
     */
    protected function getMaxSpeedWithoutBall()
    {
        return $this->getRand(Prm::PlayerMaxSpeedWithoutBallMin, Prm::PlayerMaxSpeedWithoutBallMax);
    }

    /**
     * @return float
     */
    protected function getMaxForce()
    {
        return $this->getRand(Prm::PlayerMaxForceMin, Prm::PlayerMaxForceMax);
    }

    /**
     * @return float
     */
    protected function getMass()
    {
        return $this->getRand(Prm::PlayerMassMin, Prm::PlayerMassMax);
    }

    /**
     * @param float $min
     * @param float $max
     *
     * @return float
     */
    private function getRand($min, $max)
    {
        return $min + ($max - $min) * (mt_rand() / mt_getrandmax());
    }
}
