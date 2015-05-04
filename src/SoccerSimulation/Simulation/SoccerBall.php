<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Geometry;
use SoccerSimulation\Common\D2\Transformation;
use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\D2\Wall2D;
use SoccerSimulation\Common\Messaging\Telegram;
use Cunningsoft\MatchBundle\SimpleSoccer\Render\Ball;

/**
 *  Desc: Class to implement a soccer ball. This class inherits from
 *        MovingEntity and provides further functionality for collision
 *        testing and position prediction.
 */
class SoccerBall extends MovingEntity implements \JsonSerializable
{
    /**
     * @var Vector2D
     *
     * keeps a record of the ball's position at the last update
     */
    private $oldPosition;

    /**
     * @var Wall2D[]
     *
     * a local reference to the Walls that make up the pitch boundary
     */
    private $pitchBoundary;

    /**
     * @var float
     */
    private $friction;

    /**
     * @param Vector2D $pos
     * @param float $ballSize
     * @param float $mass
     * @param float $friction
     * @param array $pitchBoundary
     */
    public function __construct(Vector2D $pos, $ballSize, $mass, $friction, array $pitchBoundary)
    {
        //set up the base class
        parent::__construct($pos,
            $ballSize,
            new Vector2D(0, 0),
            new Vector2D(0, 1),
            $mass,
            new Vector2D(1.0, 1.0), //scale     - unused
            0);                  //max force - unused

        $this->friction = $friction;
        $this->pitchBoundary = $pitchBoundary;
    }

    /**
     * tests to see if the ball has collided with a ball and reflects
     * the ball's velocity accordingly
     */

    /**
     * @param Wall2D[] $walls
     */
    public function testCollisionWithWalls(array $walls)
    {
        //test ball against each wall, find out which is closest
        $idxClosest = -1;

        $velocityNormal = Vector2D::vectorNormalize($this->velocity);

        $distToIntersection = null;

        /**
         * iterate through each wall and calculate if the ball intersects.
         * If it does then store the index into the closest intersecting wall
         */
        for ($w = 0; $w < count($walls); ++$w) {
            //assuming a collision if the ball continued on its current heading
            //calculate the point on the ball that would hit the wall. This is 
            //simply the wall's normal(inversed) multiplied by the ball's radius
            //and added to the balls center (its position)
            $thisCollisionPoint = Vector2D::staticSub($this->getPosition(),
                (Vector2D::staticMul($walls[$w]->getNormal(), $this->getBoundingRadius())));

            //calculate exactly where the collision point will hit the plane    
            if (Geometry::whereIsPoint($thisCollisionPoint,
                    $walls[$w]->getFrom(),
                    $walls[$w]->getNormal()) == Geometry::SPAN_TYPE_PLANE_BACKSIDE
            ) {
                $distToWall = Geometry::distanceToRayPlaneIntersection($thisCollisionPoint,
                    $walls[$w]->getNormal(),
                    $walls[$w]->getFrom(),
                    $walls[$w]->getNormal());

                $intersectionPoint = Vector2D::staticAdd($thisCollisionPoint,
                    (Vector2D::staticMul($walls[$w]->getNormal(), $distToWall)));

            } else {
                $distToWall = Geometry::distanceToRayPlaneIntersection($thisCollisionPoint,
                    $velocityNormal,
                    $walls[$w]->getFrom(),
                    $walls[$w]->getNormal());

                $intersectionPoint = Vector2D::staticAdd($thisCollisionPoint,
                    (Vector2D::staticMul($velocityNormal, $distToWall)));
            }

            //check to make sure the intersection point is actually on the line
            //segment
            $onLineSegment = false;

            if (Geometry::lineIntersection2D($walls[$w]->getFrom(),
                $walls[$w]->getTo(),
                Vector2D::staticSub($thisCollisionPoint, Vector2D::staticMul($walls[$w]->getNormal(), 20.0)),
                Vector2D::staticAdd($thisCollisionPoint, Vector2D::staticMul($walls[$w]->getNormal(), 20.0)))
            ) {

                $onLineSegment = true;
            }


            //Note, there is no test for collision with the end of a line segment

            //now check to see if the collision point is within range of the
            //velocity vector. [work in distance squared to avoid sqrt] and if it
            //is the closest hit found so far. 
            //If it is that means the ball will collide with the wall sometime
            //between this time step and the next one.
            $distSq = Vector2D::vectorDistanceSquared($thisCollisionPoint, $intersectionPoint);

            if (($distSq <= $this->velocity->LengthSq()) && ($distToIntersection == null || $distSq < $distToIntersection) && $onLineSegment) {
                $distToIntersection = $distSq;
                $idxClosest = $w;
            }
        }//next wall


        //to prevent having to calculate the exact time of collision we
        //can just check if the velocity is opposite to the wall normal
        //before reflecting it. This prevents the case where there is overshoot
        //and the ball gets reflected back over the line before it has completely
        //reentered the playing area.
        if (($idxClosest >= 0) && $velocityNormal->dot($walls[$idxClosest]->getNormal()) < 0) {
            $this->velocity->Reflect($walls[$idxClosest]->getNormal());
        }
    }

    /**
     * updates the ball physics, tests for any collisions and adjusts
     * the ball's velocity accordingly
     */
    public function update()
    {
        //keep a record of the old position so the goal::scored method
        //can utilize it for goal testing
        $this->oldPosition = Vector2D::createByVector2D($this->position);

        //Test for collisions
        $this->testCollisionWithWalls($this->pitchBoundary);

        //Simulate Prm.Friction. Make sure the speed is positive 
        //first though
        if ($this->velocity->LengthSq() > Prm::Friction * Prm::Friction) {
            $this->velocity->add(Vector2D::staticMul(Vector2D::vectorNormalize($this->velocity), Prm::Friction));
            $this->position->add($this->velocity);

            //update heading
            $this->heading = Vector2D::vectorNormalize($this->velocity);
        }
    }

    /**
     * Renders the ball
     */
    public function render()
    {
        throw new \Exception('dont call the render method anymore. instead the object itself is serialized and used');
    }

    //a soccer ball doesn't need to handle messages
    public function handleMessage(Telegram $message)
    {
        return false;
    }

    /**
     * applys a force to the ball in the direction of heading. Truncates
     * the new velocity to make sure it doesn't exceed the max allowable.
     */
    public function kick(Vector2D $direction, $force)
    {
        //ensure direction is normalized
        $direction->normalize();

        //calculate the acceleration
        $acceleration = Vector2D::staticDiv(Vector2D::staticMul($direction, $force), $this->mass);

        //update the velocity
        $this->velocity = $acceleration;
    }

    /**
     * Given a force and a distance to cover given by two vectors, this
     * method calculates how long it will take the ball to travel between
     * the two points
     */
    public function getTimeToCoverDistance(Vector2D $from, Vector2D $to, $force)
    {
        //this will be the velocity of the ball in the next time step *if*
        //the player was to make the pass. 
        $speed = $force / $this->mass;

        //calculate the velocity at B using the equation
        //
        //  v^2 = u^2 + 2as
        //

        //first calculate s (the distance between the two positions)
        $distanceToCover = Vector2D::Vec2DDistance($from, $to);

        $term = $speed * $speed + 2.0 * $distanceToCover * Prm::Friction;

        //if  (u^2 + 2as) is negative it means the ball cannot reach point B.
        if ($term <= 0.0) {
            return -1.0;
        }

        $v = sqrt($term);

        //it IS possible for the ball to reach B and we know its speed when it
        //gets there, so now it's easy to calculate the time using the equation
        //
        //    t = v-u
        //        ---
        //         a
        //
        return ($v - $speed) / Prm::Friction;
    }

    /**
     * given a time this method returns the ball position at that time in the
     *  future
     */
    public function getFuturePosition($time)
    {
        //using the equation s = ut + 1/2at^2, where s = distance, a = friction
        //u=start velocity

        //calculate the ut term, which is a vector
        $ut = Vector2D::staticMul($this->velocity, $time);

        //calculate the 1/2at^2 term, which is scalar
        $half_a_t_squared = 0.5 * Prm::Friction * $time * $time;

        //turn the scalar quantity into a vector by multiplying the value with
        //the normalized velocity vector (because that gives the direction)
        $scalarToVector = Vector2D::staticMul(Vector2D::vectorNormalize($this->velocity), $half_a_t_squared);

        //the predicted position is the balls position plus these two terms
        return Vector2D::staticAdd($this->getPosition(), $ut)->add($scalarToVector);
    }

    /**
     * this is used by players and goalkeepers to 'trap' a ball -- to stop
     * it dead. That player is then assumed to be in possession of the ball
     * and m_pOwner is adjusted accordingly
     */
    public function trap()
    {
        $this->velocity->Zero();
    }

    /**
     * @return Vector2D
     */
    public function getOldPosition()
    {
        return Vector2D::createByVector2D($this->oldPosition);
    }

    /**
     * positions the ball at the desired location and sets the ball's velocity to
     *  zero
     */
    public function placeAtPosition(Vector2D $position)
    {
        $this->position = Vector2D::createByVector2D($position);

        $this->oldPosition = Vector2D::createByVector2D($this->position);

        $this->velocity->Zero();
    }

    /**
     *  this can be used to vary the accuracy of a player's kick. Just call it
     *  prior to kicking the ball using the ball's position and the ball target as
     *  parameters.
     */
    public static function addNoiseToKick(Vector2D $position, Vector2D $target)
    {
        $displacement = (pi() - pi() * Prm::PlayerKickingAccuracy) * self::getRandomClamped();

        $toTarget = Vector2D::staticSub($target, $position);

        Transformation::vectorRotateAroundOrigin($toTarget, $displacement);

        return Vector2D::staticAdd($toTarget, $position);
    }

    /**
     * @return float
     */
    static private function getRandomClamped()
    {
        return mt_rand(0, 1000000) / 1000000 - mt_rand(0, 1000000) / 1000000;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'position' => $this->position,
        ];
    }
}
