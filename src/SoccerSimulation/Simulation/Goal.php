<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Geometry;
use SoccerSimulation\Common\D2\Vector2D;

/**
 * Desc:  class to define a goal for a soccer pitch. The goal is defined
 *        by two 2D vectors representing the left and right posts.
 *
 *        Each time-step the method Scored should be called to determine
 *        if a goal has been scored.
 */
class Goal implements \JsonSerializable
{
    /**
     * @var Vector2D
     */
    private $leftPost;

    /**
     * @var Vector2D
     */
    private $rightPost;

    /**
     * @var Vector2D
     *
     * a vector representing the facing direction of the goal
     */
    private $facing;

    /**
     * @var Vector2D
     *
     * the position of the center of the goal line
     */
    private $center;

    /**
     * @var int
     *
     * each time Scored() detects a goal this is incremented
     */
    private $numberOfGoalsScored;

    /**
     * @param Vector2D $center
     * @param Vector2D $facing
     */
    public function __construct(Vector2D $center, Vector2D $facing)
    {
        $this->leftPost = Vector2D::staticSub($center, new Vector2D(0, Prm::GoalWidth / 2));
        $this->rightPost = Vector2D::staticAdd($center, new Vector2D(0, Prm::GoalWidth / 2));
        $this->center = $center;
        $this->numberOfGoalsScored = 0;
        $this->facing = $facing;
    }

    /**
     * Given the current ball position and the previous ball position,
     * this method returns true if the ball has crossed the goal line
     * and increments m_iNumGoalsScored
     */
    public function hasScored(SoccerBall $ball)
    {
        if (Geometry::lineIntersection2D($ball->getPosition(), $ball->getOldPosition(), $this->leftPost, $this->rightPost))
        {
            ++$this->numberOfGoalsScored;

            return true;
        }

        return false;
    }

    /**
     * @return Vector2D
     */
    public function getCenter()
    {
        return Vector2D::createByVector2D($this->center);
    }

    /**
     * @return Vector2D
     */
    public function getFacing()
    {
        return Vector2D::createByVector2D($this->facing);
    }

    /**
     * @return Vector2D
     */
    public function getLeftPost()
    {
        return Vector2D::createByVector2D($this->leftPost);
    }

    /**
     * @return Vector2D
     */
    public function getRightPost()
    {
        return Vector2D::createByVector2D($this->rightPost);
    }

    public function getNumberOfGoalsScored()
    {
        return $this->numberOfGoalsScored;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'leftPost' => $this->leftPost,
            'rightPost' => $this->rightPost,
            'height' => Prm::GoalWidth,
            'goalsScored' => $this->numberOfGoalsScored,
        ];
    }
}
