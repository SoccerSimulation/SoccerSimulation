<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;

/**
 *  Desc:   A base class defining an entity that moves. The entity has
 *          a local coordinate system and members for defining its
 *          mass and velocity.
 */
abstract class MovingEntity extends BaseGameEntity
{
    /**
     * @var Vector2D
     */
    protected $velocity;

    /**
     * @var Vector2D
     *
     * a normalized vector pointing in the direction the entity is heading.
     */
    protected $heading;

    /**
     * @var Vector2D
     *
     * a vector perpendicular to the heading vector
     */
    protected $side;

    /**
     * @var float
     */
    protected $mass;

    /**
     * @var float
     *
     * the maximum force this entity can produce to power itself
     * (think rockets and thrust)
     */
    protected $maxForce;

    /**
     * @param Vector2D $position
     * @param float $radius
     * @param Vector2D $velocity
     * @param Vector2D $heading
     * @param float $mass
     * @param Vector2D $scale
     * @param float $maxForce
     */
    public function __construct(Vector2D $position, $radius, Vector2D $velocity, Vector2D $heading, $mass, Vector2D $scale, $maxForce)
    {
        parent::__construct(BaseGameEntity::getNextValidId());
        $this->heading = Vector2D::createByVector2D($heading);
        $this->velocity = Vector2D::createByVector2D($velocity);
        $this->mass = $mass;
        $this->side = $this->heading->getPerpendicular();
        $this->maxForce = $maxForce;

        $this->position = Vector2D::createByVector2D($position);
        $this->boundingRadius = $radius;
        $this->scale = Vector2D::createByVector2D($scale);
    }

    /**
     * @return Vector2D
     */
    public function getVelocity()
    {
        return $this->velocity;
    }

    /**
     * @param Vector2D $velocity
     */
    public function setVelocity(Vector2D $velocity)
    {
        $this->velocity = $velocity;
    }

    /**
     * @return Vector2D
     */
    public function getSide()
    {
        return $this->side;
    }

    /**
     * @return float
     */
    public function getMaxForce()
    {
        return $this->maxForce;
    }

    /**
     * @return float
     */
    public function getSpeed()
    {
        return $this->velocity->getLength();
    }

    /**
     * @return Vector2D
     */
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * @param Vector2D $new_heading
     *
     * first checks that the given heading is not a vector of zero length. If the
     * new heading is valid this fumction sets the entity's heading and side
     * vectors accordingly
     */
    public function setHeading(Vector2D $new_heading)
    {
        $this->heading = $new_heading;

        //the side vector must always be perpendicular to the heading
        $this->side = $this->heading->getPerpendicular();
    }
}
