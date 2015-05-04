<?php

namespace SoccerSimulation\Common\D2;

trait Distance
{
    /**
     * @param Vector2D $target
     *
     * @return float
     */
    public function distanceTo(Vector2D $target)
    {
        return $this->getPosition()->distanceTo($target);
    }

    /**
     * @return Vector2D
     */
    abstract public function getPosition();
}
