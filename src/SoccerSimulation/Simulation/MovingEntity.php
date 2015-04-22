<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\C2DMatrix;
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
     * the maximum speed this entity may travel at.
     */
    protected $maxSpeed;

    /**
     * @var float
     *
     * the maximum force this entity can produce to power itself
     * (think rockets and thrust)
     */
    protected $maxForce;

    /**
     * @var float
     *
     * the maximum rate (radians per second)this vehicle can rotate
     */
    protected $maxTurnRate;

    /**
     * @param Vector2D $position
     * @param float $radius
     * @param Vector2D $velocity
     * @param float $maxSpeed
     * @param Vector2D $heading
     * @param float $mass
     * @param Vector2D $scale
     * @param float $turnRate
     * @param float $maxForce
     */
    public function __construct(Vector2D $position, $radius, Vector2D $velocity, $maxSpeed, Vector2D $heading, $mass, Vector2D $scale, $turnRate, $maxForce)
    {
        parent::__construct(BaseGameEntity::getNextValidId());
        $this->heading = Vector2D::createByVector2D($heading);
        $this->velocity = Vector2D::createByVector2D($velocity);
        $this->mass = $mass;
        $this->side = $this->heading->getPerpendicular();
        $this->maxSpeed = $maxSpeed;
        $this->maxTurnRate = $turnRate;
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
    public function getMaxSpeed()
    {
        return $this->maxSpeed;
    }

    /**
     * @param float $new_speed
     */
    public function setMaxSpeed($new_speed)
    {
        $this->maxSpeed = $new_speed;
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
     * @return float
     */
    public function getMaxTurnRate()
    {
        return $this->maxTurnRate;
    }

    /**
     * @param Vector2D $target
     *
     * @return bool true when the heading is facing in the desired direction
     *
     * given a target position, this method rotates the entity's heading and
     * side vectors by an amount not greater than m_dMaxTurnRate until it
     * directly faces the target.
     */
    public function rotateHeadingToFacePosition(Vector2D $target)
    {
        $toTarget = Vector2D::vectorNormalize(Vector2D::staticSub($target, $this->position));

        //first determine the angle between the heading vector and the target
        $angle = acos($this->heading->dot($toTarget));

        //sometimes m_vHeading.Dot(toTarget) == 1.000000002
        if(is_nan($angle)) {
            $angle = 0;
        }

        //return true if the player is facing the target
        if ($angle < 0.00001)
        {
            return true;
        }

        //clamp the amount to turn to the max turn rate
        if ($angle > $this->maxTurnRate)
        {
            $angle = $this->maxTurnRate;
        }

        //The next few lines use a rotation matrix to rotate the player's heading
        //vector accordingly
        $RotationMatrix = new C2DMatrix();

        //notice how the direction of rotation has to be determined when creating
        //the rotation matrix
        $RotationMatrix->rotate($angle * $this->heading->sign($toTarget));
        $RotationMatrix->transformVector2Ds($this->heading);
        $RotationMatrix->transformVector2Ds($this->velocity);

        //finally recreate m_vSide
        $this->side = $this->heading->getPerpendicular();

        return false;
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
