<?php

namespace SoccerSimulation\Common\Game;

use SoccerSimulation\Common\D2\Vector2D;

/**
 *  Desc:   Defines a rectangular region. A region has an identifying
 *          number, and four corners.
 */
class Region implements \JsonSerializable
{
    const REGION_MODIFIER_HALFSIZE = 'region_modifier_halfsize';
    const REGION_MODIFIER_NORMAL = 'region_modifier_normal';

    public static $halfsize = self::REGION_MODIFIER_HALFSIZE;
    public static $normal = self::REGION_MODIFIER_NORMAL;

    /**
     * @var float
     */
    protected $top;

    /**
     * @var float
     */
    protected $left;

    /**
     * @var float
     */
    protected $right;

    /**
     * @var float
     */
    protected $bottom;

    /**
     * @var float
     */
    protected $width;

    /**
     * @var float
     */
    protected $height;

    /**
     * @var Vector2D
     */
    protected $center;

    /**
     * @var int
     */
    protected $id;

    public function __construct($left = 0, $top = 0, $right = 0, $bottom = 0, $id = -1)
    {
        $this->top = $top;
        $this->right = $right;
        $this->left = $left;
        $this->bottom = $bottom;
        $this->id = $id;

        //calculate center of region
        $this->center = new Vector2D(($left + $right) * 0.5, ($top + $bottom) * 0.5);

        $this->width = abs($right - $left);
        $this->height = abs($bottom - $top);
    }

    /**
     * returns true if the given position lays inside the region. The
     * region modifier can be used to contract the region bounderies
     */
    public function isInside(Vector2D $pos, $r = self::REGION_MODIFIER_NORMAL)
    {
        if ($r == self::REGION_MODIFIER_NORMAL) {
            return (($pos->x > $this->left) && ($pos->x < $this->right)
                && ($pos->y > $this->top) && ($pos->y < $this->bottom));
        } else {
            $marginX = $this->width * 0.25;
            $marginY = $this->height * 0.25;

            return (($pos->x > ($this->left + $marginX)) && ($pos->x < ($this->right - $marginX))
                && ($pos->y > ($this->top + $marginY)) && ($pos->y < ($this->bottom - $marginY)));
        }
    }

    public function getTop()
    {
        return $this->top;
    }

    public function getBottom()
    {
        return $this->bottom;
    }

    public function getLeft()
    {
        return $this->left;
    }

    public function getRight()
    {
        return $this->right;
    }

    public function getWidth()
    {
        return abs($this->right - $this->left);
    }

    public function getHeight()
    {
        return abs($this->top - $this->bottom);
    }

    public function getLength()
    {
        return max($this->getWidth(), $this->getHeight());
    }

    public function getCenter()
    {
        return Vector2D::createByVector2D($this->center);
    }

    public function ID()
    {
        return $this->id;
    }

    public function jsonSerialize()
    {
        return [
            'left' => $this->left,
            'right' => $this->right,
            'top' => $this->top,
            'bottom' => $this->bottom,
            'id' => $this->id,
        ];
    }
}
