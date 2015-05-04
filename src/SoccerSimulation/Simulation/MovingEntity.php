<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\Messaging\Telegram;

/**
 *  Desc:   A base class defining an entity that moves. The entity has
 *          a local coordinate system and members for defining its
 *          mass and velocity.
 */
abstract class MovingEntity
{
    /**
     * @var int
     *
     * each entity has a unique ID
     */
    protected $id;

    /**
     * @var int
     *
     * this is the next valid ID. Each time a MovingEntity is instantiated
     * this value is updated
     */
    private static $nextValidId = 0;

    /**
     * @var Vector2D
     *
     * its location in the environment
     */
    protected $position;

    /**
     * @var Vector2D
     */
    protected $scale;

    /**
     * @var double
     *
     * the magnitude of this object's bounding radius
     */
    protected $boundingRadius = 0;

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
     * @var float
     */
    protected $mass;

    /**
     * @param Vector2D $position
     * @param float $radius
     * @param Vector2D $heading
     * @param float $mass
     * @param Vector2D $scale
     */
    public function __construct(
        Vector2D $position,
        $radius,
        Vector2D $heading,
        $mass,
        Vector2D $scale
    ) {
        $this->id = self::$nextValidId++;
        $this->scale = new Vector2D(1, 1);
        $this->position = new Vector2D();
        $this->heading = Vector2D::createByVector2D($heading);
        $this->velocity = new Vector2D(0, 0);
        $this->mass = $mass;

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
    }

    public function update()
    {
    }

    /**
     * @param Telegram $message
     *
     * @return bool
     */
    public function handleMessage(Telegram $message)
    {
        return false;
    }

    /**
     * @return Vector2D
     */
    public function getPosition()
    {
        return Vector2D::createByVector2D($this->position);
    }

    /**
     * @param Vector2D $position
     */
    public function placeAtPosition(Vector2D $position)
    {
        $this->position = Vector2D::createByVector2D($position);
    }

    /**
     * @return float
     */
    public function getBoundingRadius()
    {
        return $this->boundingRadius;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
}
