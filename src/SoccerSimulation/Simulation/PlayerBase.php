<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\C2DMatrix;
use SoccerSimulation\Common\D2\Distance;
use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\Event\EventGenerator;
use SoccerSimulation\Common\FSM\StateMachine;
use SoccerSimulation\Common\Game\Region;
use SoccerSimulation\Common\Messaging\MessageDispatcher;
use SoccerSimulation\Common\Misc\AutoList;

/**
 *  Desc: Definition of a soccer player base class. <del>The player inherits
 *        from the autolist class so that any player created will be
 *        automatically added to a list that is easily accesible by any
 *        other game objects.</del> (mainly used by the steering behaviors and
 *        player state classes)
 */
abstract class PlayerBase extends MovingEntity implements Nameable
{
    use EventGenerator;
    use Distance;

    /**
     * @var StateMachine
     *
     * an instance of the state machine class
     */
    protected $stateMachine;

    /**
     * @var SoccerTeam
     */
    protected $team;

    /**
     * @var SteeringBehaviors
     */
    protected $steering;

    /**
     * @var int
     *
     * the region that this player is assigned to.
     */
    protected $homeRegion;

    /**
     * @var float
     */
    protected $maxSpeedWithBall;

    /**
     * @var float
     */
    protected $maxSpeedWithoutBall;

    /**
     * @var int
     *
     * the region this player moves to before kickoff
     */
    protected $defaultRegion;

    /**
     * @var float
     *
     * the distance to the ball (in squared-space). This value is queried
     * a lot so it's calculated once each time-step and stored here.
     */
    protected $distanceToBallSquared;

    /**
     * @var array
     */
    private $debugMessages = array();

    /**
     * @var float
     *
     * the maximum rate (radians per second)this player can rotate
     */
    protected $maxTurnRate;

    /**
     * @var float
     *
     * the maximum force this entity can produce to power itself
     * (think rockets and thrust)
     */
    protected $maxForce;

    /**
     * @param SoccerTeam $homeTeam
     * @param int $homeRegion
     * @param float $mass
     * @param float $maxForce
     * @param float $maxSpeedWithBall
     * @param float $maxSpeedWithoutBall
     */
    public function __construct(
        SoccerTeam $homeTeam,
        $homeRegion,
        $mass,
        $maxForce,
        $maxSpeedWithBall,
        $maxSpeedWithoutBall
    ) {
        $scale = Prm::PlayerScale;
        parent::__construct(
            $homeTeam->getPitch()->getRegionFromIndex($homeRegion)->getCenter(),
            $scale * 5.0,
            new Vector2D(0, -1),
            $mass,
            new Vector2D($scale, $scale)
        );
        $this->team = $homeTeam;
        $this->distanceToBallSquared = null; // @todo needs to be maxFloat
        $this->homeRegion = $homeRegion;
        $this->defaultRegion = $homeRegion;
        $this->boundingRadius = 5;
        $this->maxSpeedWithBall = $maxSpeedWithBall;
        $this->maxSpeedWithoutBall = $maxSpeedWithoutBall;
        $this->maxTurnRate = Prm::PlayerMaxTurnRate;
        $this->maxForce = $maxForce;

        //set up the steering behavior class
        $this->steering = new SteeringBehaviors($this, $this->getBall());
        $this->steering->activateSeparation();

        //a player's start target is its start position (because it's just waiting)
        $this->steering->setTarget($homeTeam->getPitch()->getRegionFromIndex($homeRegion)->getCenter());
        (new AutoList())->add($this);
    }

    /**
     * @return float
     */
    public function getMaxTurnRate()
    {
        return $this->maxTurnRate;
    }

    /**
     *  returns true if there is an opponent within this player's
     *  comfort zone
     *
     * @return bool
     */
    public function isThreatened()
    {
        /** @var PlayerBase[] $members */
        $members = $this->getTeam()->getOpponent()->getPlayers();
        //check against all opponents to make sure non are within this
        //player's comfort zone
        foreach ($members as $currentOpponent) {
            //calculate distance to the player. if dist is less than our
            //comfort zone, and the opponent is infront of the player, return true
            if ($this->isPositionInFrontOfPlayer($currentOpponent->getPosition()) && $this->distanceTo($currentOpponent->getPosition()) < Prm::PlayerComfortZoneSq()) {
                return true;
            }

        }

        return false;
    }

    /**
     * @return float
     */
    public function getMaxSpeed()
    {
        return $this->hasBall() ? $this->maxSpeedWithBall : $this->maxSpeedWithoutBall;
    }

    /**
     *  rotates the player to face the ball
     */
    public function trackBall()
    {
        $this->rotateHeadingToFacePosition($this->getBall()->getPosition());
    }

    /**
     * sets the player's heading to point at the current target
     */
    public function trackTarget()
    {
        $this->setHeading(Vector2D::vectorNormalize(Vector2D::staticSub($this->getSteering()->getTarget(),
            $this->getPosition())));
    }

    /**
     * determines the player who is closest to the SupportSpot and messages him
     * to tell him to change state to SupportAttacker
     */
    public function findSupport()
    {
        //if there is no support we need to find a suitable player.
        if ($this->getTeam()->getSupportingPlayer() == null) {
            $bestSupportPlayer = $this->getTeam()->determineBestSupportingAttacker();
            $this->getTeam()->setSupportingPlayer($bestSupportPlayer);
            MessageDispatcher::getInstance()->dispatch($this, $this->getTeam()->getSupportingPlayer(),
                new MessageTypes(MessageTypes::Msg_SupportAttacker), null);
        }

        $bestSupportPlayer = $this->getTeam()->determineBestSupportingAttacker();

        //if the best player available to support the attacker changes, update
        //the pointers and send messages to the relevant players to update their
        //states
        if ($bestSupportPlayer != null && ($bestSupportPlayer != $this->getTeam()->getSupportingPlayer())) {

            if ($this->getTeam()->getSupportingPlayer() != null) {
                MessageDispatcher::getInstance()->dispatch($this, $this->getTeam()->getSupportingPlayer(),
                    new MessageTypes(MessageTypes::Msg_GoHome), null);
            }

            $this->getTeam()->setSupportingPlayer($bestSupportPlayer);

            MessageDispatcher::getInstance()->dispatch($this, $this->getTeam()->getSupportingPlayer(),
                new MessageTypes(MessageTypes::Msg_SupportAttacker), null);
        }
    }

    /**
     * @return bool
     */
    public function isBallWithinKeeperRange()
    {
        return $this->distanceTo($this->getBall()->getPosition()) < Prm::KeeperInBallRange;
    }

    /**
     * @return bool
     */
    public function isBallWithinKickingRange()
    {
        return $this->distanceTo($this->getBall()->getPosition()) < Prm::PlayerKickingDistance();
    }

    /**
     * @return bool
     */
    public function isBallWithinReceivingRange()
    {
        return $this->distanceTo($this->getBall()->getPosition()) < Prm::BallWithinReceivingRange;
    }

    /**
     * @return bool
     */
    public function isInHomeRegion()
    {
        $homeRegion = $this->getPitch()->getRegionFromIndex($this->homeRegion);

        $regionModifier = $this->isGoalkeeper() ? Region::REGION_MODIFIER_HALFSIZE : Region::REGION_MODIFIER_NORMAL;

        return $homeRegion->isInside($this->getPosition(), $regionModifier);
    }

    /**
     * @return bool
     */
    public function isAheadOfAttacker()
    {
        return $this->getDistanceToOpponentGoal() < $this->getTeam()->getControllingPlayer()->getDistanceToOpponentGoal();
    }

    /**
     * @return int
     */
    public function getDistanceToOpponentGoal()
    {
        return abs($this->getPosition()->x - $this->getTeam()->getOpponentsGoal()->getCenter()->x);
    }

    /**
     * @return bool
     */
    public function isAtTarget()
    {
        return $this->distanceTo($this->steering->getTarget()) < Prm::PlayerInTargetRange;
    }

    /**
     * @return bool
     */
    public function isClosestTeamMemberToBall()
    {
        return $this->getTeam()->getPlayerClosestToBall() == $this;
    }

    /**
     * @param Vector2D $position
     *
     * @return bool
     *
     * true if the point specified by 'position' is located in
     * front of the player
     */
    public function isPositionInFrontOfPlayer(Vector2D $position)
    {
        $toSubject = Vector2D::staticSub($position, $this->getPosition());

        return $toSubject->dot($this->getHeading()) > 0;
    }

    /**
     * @return bool
     */
    public function isClosestPlayerOnPitchToBall()
    {
        return $this->isClosestTeamMemberToBall() && ($this->getDistanceToBallSquared() < $this->getTeam()->getOpponent()->getClosestDistanceToBallSquared());
    }

    /**
     * @return bool
     */
    public function isControllingPlayer()
    {
        return $this->getTeam()->getControllingPlayer() == $this;
    }

    /**
     * @return bool
     */
    public function hasBall()
    {
        return $this->isControllingPlayer() && $this->isBallWithinReceivingRange();
    }

    /**
     * @return true if the player is located in the designated 'hot region' --
     * the area close to the opponent's goal (1/3 of the pitch)
     */
    public function isInHotRegion()
    {
        return abs($this->getPosition()->x - $this->getTeam()->getOpponentsGoal()->getCenter()->x) < $this->getPitch()->getPlayingArea()->getLength() / 3.0;
    }

    public function getDistanceToBallSquared()
    {
        return $this->distanceToBallSquared;
    }

    public function setDistanceToBallSquared($val)
    {
        $this->distanceToBallSquared = $val;
    }

    /**
     *  Calculate distance to opponent's/home goal. Used frequently by the passing methods
     */
    public function getDistanceToOpponentsGoal()
    {
        return abs($this->getPosition()->x - $this->getTeam()->getOpponentsGoal()->getCenter()->x);
    }

    /**
     * @return float
     */
    public function getDistanceToHomeGoal()
    {
        return abs($this->getPosition()->x - $this->getTeam()->getHomeGoal()->getCenter()->x);
    }

    public function setDefaultHomeRegion()
    {
        $this->homeRegion = $this->defaultRegion;
    }

    /**
     * @return SoccerBall
     */
    public function getBall()
    {
        return $this->getTeam()->getPitch()->getBall();
    }

    /**
     * @return SoccerPitch
     */
    public function getPitch()
    {
        return $this->getTeam()->getPitch();
    }

    public function getSteering()
    {
        return $this->steering;
    }

    /**
     * @return Region
     */
    public function getHomeRegion()
    {
        return $this->getPitch()->getRegionFromIndex($this->homeRegion);
    }

    /**
     * @param int $region
     */
    public function setHomeRegion($region)
    {
        $this->homeRegion = $region;
    }

    /**
     * @return SoccerTeam
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param string $message
     */
    public function addDebugMessages($message)
    {
        $this->debugMessages[] = $message;
    }

    /**
     * @return array
     */
    public function getDebugMessages()
    {
        return $this->debugMessages;
    }

    public function update()
    {
        $this->debugMessages = array();
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
        if (is_nan($angle)) {
            $angle = 0;
        }

        //return true if the player is facing the target
        if ($angle < 0.00001) {
            return true;
        }

        //clamp the amount to turn to the max turn rate
        if ($angle > $this->maxTurnRate) {
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

        return false;
    }

    /**
     * @return bool
     */
    public function isBallAhead()
    {
        return $this->getDotProductToBall() > 0;
    }

    /**
     * @return float
     */
    public function getShootingForce()
    {
        return Prm::MaxShootingForce * $this->getDotProductToBall();
    }

    /**
     * @return float
     */
    public function getPassingForce()
    {
        return Prm::MaxPassingForce * $this->getDotProductToBall();
    }

    /**
     * @return float
     */
    private function getDotProductToBall()
    {
        $toBall = Vector2D::staticSub($this->getBall()->getPosition(), $this->position);

        return $this->heading->dot(Vector2D::vectorNormalize($toBall));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return join('', array_slice(explode('\\', get_class($this)), -1)) . ' ' . $this->id;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'heading' => $this->heading,
            'target' => $this->steering->getTarget(),
            'steeringForce' => Vector2D::staticAdd($this->position,
                Vector2D::staticMul($this->steering->getForce(), 50)),
            'state' => $this->stateMachine->getNameOfCurrentState(),
            'threatened' => $this->isControllingPlayer() && $this->isThreatened(),
            'isInHotRegion' => $this->isInHotRegion(),
            'debug' => $this->debugMessages,
        ];
    }

    /**
     * @return StateMachine
     */
    public function getStateMachine()
    {
        return $this->stateMachine;
    }

    /**
     * @return float
     */
    public function getMaxForce()
    {
        return $this->maxForce;
    }

    /**
     * @return bool
     */
    abstract public function isGoalkeeper();
}
