<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;

/**
 * a data structure to hold the values and positions of each spot
 */
class SupportSpot implements \JsonSerializable
{
    /**
     * @var Vector2D
     */
    public $m_vPos;

    /**
     * @var float
     */
    public $m_dScore;

    /**
     * @param Vector2D $pos
     * @param float $value
     */
    public function __construct(Vector2D $pos, $value)
    {
        $this->m_vPos = Vector2D::createByVector2D($pos);
        $this->m_dScore = $value;
    }

    public function jsonSerialize()
    {
        return [
            'position' => $this->m_vPos,
            'score' => $this->m_dScore,
        ];
    }
}
