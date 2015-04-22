<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\Misc\AutoList;

/**
 *
 *  Desc:   class to encapsulate steering behaviors for a soccer player
 */
class SteeringBehaviors
{
    //Arrive makes use of these to determine how quickly a vehicle
    //should decelerate to its target
    const DECELERATION_FAST = 1;
    const DECELERATION_NORMAL = 2;
    const DECELERATION_SLOW = 3;

    const FLAG_SEEK = 1;
    const FLAG_ARRIVE = 2;
    const FLAG_SEPARATION = 3;
    const FLAG_PURSUIT = 4;
    const FLAG_INTERPOSE = 5;

    /**
     * @var PlayerBase
     */
    private $player;

    /**
     * @var SoccerBall
     */
    private $ball;

    //the steering force created by the combined effect of all
    //the selected behaviors
    /**
     * @var Vector2D
     */
    private $steeringForce;
    //the current target (usually the ball or predicted ball position)
    private $target;

    //the distance the player tries to interpose from the target
    private $interposeDistance;
    //multipliers. 
    private $multiplierSeparation;
    //how far it can 'see'
    private $viewDistance;

    private $flags = array();

    //used by group behaviors to tag neighbours
    private $tagged;

    public function __construct(PlayerBase $agent, SoccerBall $ball)
    {
        $this->steeringForce = new Vector2D();
        $this->target = new Vector2D();
        $this->player = $agent;
        $this->multiplierSeparation = Prm::SeparationCoefficient;
        $this->tagged = false;
        $this->viewDistance = Prm::ViewDistance;
        $this->ball = $ball;
        $this->interposeDistance = 0;
    }

    /**
     * Given a target, this behavior returns a steering force which will
     * allign the agent with the target and move the agent in the desired
     * direction
     */
    private function seek(Vector2D $target)
    {

        $DesiredVelocity = Vector2D::vectorNormalize(Vector2D::staticMul(Vector2D::staticSub($target, $this->player->getPosition()), $this->player->getMaxSpeed()));

        return Vector2D::staticSub($DesiredVelocity, $this->player->getVelocity());
    }

    /**
     * This behavior is similar to seek but it attempts to arrive at the
     *  target with a zero velocity
     */
    private function arrive(Vector2D $TargetPos, $deceleration)
    {
        $ToTarget = Vector2D::staticSub($TargetPos, $this->player->getPosition());

        //calculate the distance to the target
        $dist = $ToTarget->getLength();

        if ($dist > 0)
        {
            //because Deceleration is enumerated as an int, this value is required
            //to provide fine tweaking of the deceleration..
            $DecelerationTweaker = 0.3;

            //calculate the speed required to reach the target given the desired
            //deceleration
            $speed = $dist / ($deceleration * $DecelerationTweaker);

            //make sure the velocity does not exceed the max
            $speed = min($speed, $this->player->getMaxSpeed());

            //from here proceed just like Seek except we don't need to normalize 
            //the ToTarget vector because we have already gone to the trouble
            //of calculating its length: dist. 
            $DesiredVelocity = Vector2D::staticMul($ToTarget, $speed / $dist);

            return Vector2D::staticSub($DesiredVelocity, $this->player->getVelocity());
        }

        return new Vector2D(0, 0);
    }

    /**
     * This behavior predicts where its prey will be and seeks
     * to that location
     * This behavior creates a force that steers the agent towards the
     * ball
     */
    private function pursuit(SoccerBall $ball)
    {
        $ToBall = Vector2D::staticSub($ball->getPosition(), $this->player->getPosition());

        //the lookahead time is proportional to the distance between the ball
        //and the pursuer; 
        $LookAheadTime = 0.0;

        if ($ball->getSpeed() != 0.0)
        {
            $LookAheadTime = $ToBall->getLength() / $ball->getSpeed();
        }

        //calculate where the ball will be at this time in the future
        $m_vTarget = $ball->getFuturePosition($LookAheadTime);

        //now seek to the predicted future position of the ball
        return $this->arrive($m_vTarget, self::DECELERATION_FAST);
    }

    /**
     *
     * this calculates a force repelling from the other neighbors
     */
    private function separation()
    {
        //iterate through all the neighbors and calculate the vector from them
        $SteeringForce = new Vector2D();

        /** @var PlayerBase[] $AllPlayers */
        $AllPlayers = (new AutoList())->GetAllMembers();
        foreach ($AllPlayers as $curPlyr)
        {
            //make sure this agent isn't included in the calculations and that
            //the agent is close enough
            if (($curPlyr != $this->player) && $curPlyr->getSteering()->isTagged())
            {
                $ToAgent = Vector2D::staticSub($this->player->getPosition(), $curPlyr->getPosition());

                //scale the force inversely proportional to the agents distance  
                //from its neighbor.
                $SteeringForce->add(Vector2D::staticDiv(Vector2D::vectorNormalize($ToAgent), $ToAgent->getLength()));
            }
        }

        return $SteeringForce;
    }

    /**
     * Given an opponent and an object position this method returns a
     * force that attempts to position the agent between them
     */
    private function interpose(SoccerBall $ball,
        Vector2D $target,
        $DistFromTarget)
    {
        return $this->arrive(Vector2D::staticAdd($target, Vector2D::staticMul(Vector2D::vectorNormalize(Vector2D::staticSub($ball->getPosition(), $target)),
            $DistFromTarget)), self::DECELERATION_NORMAL);
    }

    /**
     *  tags any vehicles within a predefined radius
     */
    private function findNeighbours()
    {
        /** @var PlayerBase[] $AllPlayers */
        $AllPlayers = (new AutoList())->GetAllMembers();
        foreach ($AllPlayers as $curPlyr)
        {
            //first clear any current tag
            $curPlyr->getSteering()->unTag();

            //work in distance squared to avoid sqrts
            $to = Vector2D::staticSub($curPlyr->getPosition(), $this->player->getPosition());

            if ($to->LengthSq() < ($this->viewDistance * $this->viewDistance))
            {
                $curPlyr->getSteering()->tag();
            }
        }
        //next
    }

    /**
     * this function tests if a specific bit of m_iFlags is set
     */
    private function isActive($bt)
    {
        return isset($this->flags[$bt]);
    }

    /**
     *  This function calculates how much of its max steering force the
     *  vehicle has left to apply and then applies that amount of the
     *  force to add.
     */
    private function accumulateForce(Vector2D $sf, Vector2D $ForceToAdd)
    {
        //first calculate how much steering force we have left to use
        $MagnitudeSoFar = $sf->getLength();

        $magnitudeRemaining = $this->player->getMaxForce() - $MagnitudeSoFar;

        //return false if there is no more force left to use
        if ($magnitudeRemaining <= 0.0)
        {
            return false;
        }

        //calculate the magnitude of the force we want to add
        $MagnitudeToAdd = $ForceToAdd->getLength();

        //now calculate how much of the force we can really add  
        if ($MagnitudeToAdd > $magnitudeRemaining)
        {
            $MagnitudeToAdd = $magnitudeRemaining;
        }

        //add it to the steering force
        $sf->add(Vector2D::staticMul(Vector2D::vectorNormalize($ForceToAdd), $MagnitudeToAdd));

        return true;
    }

    /**
     * this method calls each active steering behavior and acumulates their
     *  forces until the max steering force magnitude is reached at which
     *  time the function returns the steering force accumulated to that
     *  point
     */
    private function sumForces()
    {
        $force = new Vector2D();

        //the soccer players must always tag their neighbors
        $this->findNeighbours();

        if ($this->isActive(self::FLAG_SEPARATION))
        {
            $force->add(Vector2D::staticMul($this->separation(), $this->multiplierSeparation));

            if (!$this->accumulateForce($this->steeringForce, $force))
            {
                return $this->steeringForce;
            }
        }

        if ($this->isActive(self::FLAG_SEEK))
        {
            $force->add($this->seek($this->target));

            if (!$this->accumulateForce($this->steeringForce, $force))
            {
                return $this->steeringForce;
            }
        }

        if ($this->isActive(self::FLAG_ARRIVE))
        {
            $force->add($this->arrive($this->target, self::DECELERATION_FAST));

            if (!$this->accumulateForce($this->steeringForce, $force))
            {
                return $this->steeringForce;
            }
        }

        if ($this->isActive(self::FLAG_PURSUIT))
        {
            $force->add($this->pursuit($this->ball));

            if (!$this->accumulateForce($this->steeringForce, $force))
            {
                return $this->steeringForce;
            }
        }

        if ($this->isActive(self::FLAG_INTERPOSE))
        {
            $force->add($this->interpose($this->ball, $this->target, $this->interposeDistance));

            if (!$this->accumulateForce($this->steeringForce, $force))
            {
                return $this->steeringForce;
            }
        }

        return $this->steeringForce;
    }

    /**
     * calculates the overall steering force based on the currently active
     * steering behaviors.
     */
    public function calculate()
    {
        //reset the force
        $this->steeringForce->Zero();

        //this will hold the value of each individual steering force
        $this->steeringForce = $this->sumForces();

        //make sure the force doesn't exceed the vehicles maximum allowable
        $this->steeringForce->truncate($this->player->getMaxForce());

        return Vector2D::createByVector2D($this->steeringForce);
    }

    /**
     * calculates the component of the steering force that is parallel
     * with the vehicle heading
     */
    public function getForwardComponent()
    {
        return $this->player->getHeading()->dot($this->steeringForce);
    }

    /**
     * calculates the component of the steering force that is perpendicuar
     * with the vehicle heading
     */
    public function getSideComponent()
    {
        return $this->player->getSide()->dot($this->steeringForce) * $this->player->getMaxTurnRate();
    }

    public function getForce()
    {
        return $this->steeringForce;
    }

    /**
     * renders visual aids and info for seeing how each behavior is
     * calculated
     */
    public function render()
    {
        //render the steering force
        $to = Vector2D::staticAdd($this->player->getPosition(), Vector2D::staticMul($this->steeringForce, 20));

        return $to;
    }

    public function getTarget()
    {
        return Vector2D::createByVector2D($this->target);
    }

    public function setTarget(Vector2D $t)
    {
        $this->target = Vector2D::createByVector2D($t);
    }

    public function isTagged()
    {
        return $this->tagged;
    }

    public function tag()
    {
        $this->tagged = true;
    }

    public function unTag()
    {
        $this->tagged = false;
    }

    public function activateSeek()
    {
        $this->flags[self::FLAG_SEEK] = true;
    }

    public function activateArrive()
    {
        $this->flags[self::FLAG_ARRIVE] = true;
    }

    public function activatePursuit()
    {
        $this->flags[self::FLAG_PURSUIT] = true;
    }

    public function activateSeparation()
    {
        $this->flags[self::FLAG_SEPARATION] = true;
    }

    public function activateInterpose($d)
    {
        $this->flags[self::FLAG_INTERPOSE] = true;
        $this->interposeDistance = $d;
    }

    public function deactivateSeek()
    {
        unset($this->flags[self::FLAG_SEEK]);
    }

    public function deactivateArrive()
    {
        unset($this->flags[self::FLAG_ARRIVE]);
    }

    public function deactivatePursuit()
    {
        unset($this->flags[self::FLAG_PURSUIT]);
    }

    public function deactivateSeparation()
    {
        unset($this->flags[self::FLAG_SEPARATION]);
    }

    public function deactivateInterpose()
    {
        unset($this->flags[self::FLAG_INTERPOSE]);
    }

    public function isSeekActive()
    {
        return $this->isActive(self::FLAG_SEEK);
    }

    public function isArriveActive()
    {
        return $this->isActive(self::FLAG_ARRIVE);
    }

    public function isPursuitActive()
    {
        return $this->isActive(self::FLAG_PURSUIT);
    }

    public function isSeparationActive()
    {
        return $this->isActive(self::FLAG_SEPARATION);
    }

    public function isInterposeActive()
    {
        return $this->isActive(self::FLAG_INTERPOSE);
    }
}
