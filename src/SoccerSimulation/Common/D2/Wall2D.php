<?php

namespace SoccerSimulation\Common\D2;

/**
 *  Desc:   class to create and render 2D walls. Defined as the two
 *          vectors A - B with a perpendicular normal.
 */
class Wall2D
{
    /**
     * @var Vector2D
     */
    private $from;

    /**
     * @var Vector2D
     */
    private $to;

    /**
     * @var Vector2D
     */
    private $normal;

    public function __construct(Vector2D $from, Vector2D $to)
    {
        $this->from = $from;
        $this->to = $to;
        $this->calculateNormal();
    }

    private function calculateNormal()
    {
        $temp = Vector2D::vectorNormalize(Vector2D::staticSub($this->to, $this->from));

        $this->normal = new Vector2D(-$temp->y, $temp->x);
    }

    /**
     * @return Vector2D
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return Vector2D
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return Vector2D
     */
    public function getNormal()
    {
        return $this->normal;
    }
}
