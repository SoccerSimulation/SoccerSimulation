<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Geometry;
use SoccerSimulation\Common\D2\Transformation;
use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\Event\EventGenerator;
use SoccerSimulation\Common\FSM\StateMachine;
use SoccerSimulation\Common\Messaging\MessageDispatcher;
use SoccerSimulation\Simulation\FieldPlayerStates\ReturnToHomeRegion;
use SoccerSimulation\Simulation\FieldPlayerStates\Wait;
use SoccerSimulation\Simulation\TeamStates\Defending;

/**
 *  Desc:   class to define a team of soccer playing agents. A SoccerTeam
 *          contains several field players and one goalkeeper. A SoccerTeam
 *          is implemented as a finite state machine and has states for
 *          attacking, defending, and KickOff.
 */
class SoccerTeam implements \JsonSerializable, Nameable
{
    use EventGenerator;

    const COLOR_BLUE = 'blue';
    const COLOR_RED = 'red';

    public static $blue = self::COLOR_BLUE;
    public static $red = self::COLOR_RED;

    /**
     * @var StateMachine
     *
     * an instance of the state machine class
     */
    private $stateMachine;

    /**
     * @var string
     *
     * the team must know its own color!
     */
    private $color;

    /**
     * @var Goalkeeper
     */
    private $goalkeeper;

    /**
     * @var FieldPlayer[]
     */
    private $fieldPlayers = [];

    /**
     * @var SoccerPitch
     */
    private $pitch;

    /**
     * @var Goal
     */
    private $opponentsGoal;

    /**
     * @var Goal
     */
    private $homeGoal;

    /**
     * @var SoccerTeam
     */
    private $opponent;

    /**
     * @var PlayerBase
     */
    private $controllingPlayer;

    /**
     * @var PlayerBase
     */
    private $supportingPlayer;

    /**
     * @var PlayerBase
     */
    private $receivingPlayer;

    /**
     * @var PlayerBase
     */
    private $playerClosestToBall;

    /**
     * @var float
     *
     * the squared distance the closest player is from the ball
     */
    private $distanceToBallOfClosestPlayerSquared;

    /**
     * @var SupportSpotCalculator
     *
     * players use this to determine strategic positions on the playing field
     */
    private $supportSpotCalculator;

    /**
     * @var array
     */
    private $debugMessages = array();

    /**
     * @return PlayerBase[]
     */
    public function getPlayers()
    {
        return array_merge([$this->goalkeeper], $this->fieldPlayers);
    }

    /**
     * called each frame. Sets m_pClosestPlayerToBall to point to the player
     * closest to the ball.
     */
    private function calculateClosestPlayerToBall()
    {
        $closestSoFar = null;

        foreach ($this->getPlayers() as $player) {
            //calculate the dist. Use the squared value to avoid sqrt
            $dist = Vector2D::vectorDistanceSquared($player->getPosition(), $this->getPitch()->getBall()->getPosition());

            //keep a record of this value for each player
            $player->setDistanceToBallSquared($dist);

            if ($closestSoFar === null || $dist < $closestSoFar) {
                $closestSoFar = $dist;

                $this->playerClosestToBall = $player;
            }
        }

        $this->distanceToBallOfClosestPlayerSquared = $closestSoFar;
    }

    public function __construct(Goal $homeGoal, Goal $opponentsGoal, SoccerPitch $pitch, $color)
    {
        $this->opponentsGoal = $opponentsGoal;
        $this->homeGoal = $homeGoal;
        $this->opponent = null;
        $this->pitch = $pitch;
        $this->color = $color;
        $this->distanceToBallOfClosestPlayerSquared = 0;
        $this->supportingPlayer = null;
        $this->receivingPlayer = null;
        $this->controllingPlayer = null;
        $this->playerClosestToBall = null;

        //setup the state machine
        $this->stateMachine = new StateMachine($this, Defending::getInstance(), Defending::getInstance(), null);

        //create the players and goalkeeper
        $this->createPlayers();

        //create the sweet spot calculator
        $this->supportSpotCalculator = new SupportSpotCalculator(Prm::NumSupportSpotsX, Prm::NumSupportSpotsY, $this);
    }

    /**
     * creates all the players for this team
     */
    private function createPlayers()
    {
        $fieldPlayerFactory = new FieldPlayerFactory();
        $goalKeeperFactory = new GoalKeeperFactory();

        $this->goalkeeper = $goalKeeperFactory->create($this, $this->color == self::COLOR_RED ? 80 : 3);
        $this->fieldPlayers = $fieldPlayerFactory->createCompleteLineUp($this);
    }

    /**
     *  iterates through each player's update function and calculates
     *  frequently accessed info
     */
    public function update()
    {
        //this information is used frequently so it's more efficient to 
        //calculate it just once each frame
        $this->calculateClosestPlayerToBall();

        //the team state machine switches between attack/defense behavior. It
        //also handles the 'kick off' state where a team must return to their
        //kick off positions before the whistle is blown
        $this->stateMachine->update();
        $this->raiseMultiple($this->stateMachine->releaseEvents());

        //now update each player
        foreach ($this->getPlayers() as $player) {
            $player->update();
            $this->raiseMultiple($player->releaseEvents());
        }
    }

    /**
     * calling this changes the state of all field players to that of
     * ReturnToHomeRegion. Mainly used when a goal keeper has
     * possession
     */
    public function returnAllFieldPlayersToHome()
    {
        foreach ($this->getPlayers() as $player) {
            if (!$player->isGoalkeeper()) {
                MessageDispatcher::getInstance()->dispatch($this->goalkeeper, $player,
                    new MessageTypes(MessageTypes::Msg_GoHome), null);
            }
        }
    }

    /**
     *  Given a ball position, a kicking power and a reference to a vector2D
     *  this function will sample random positions along the opponent's goal-
     *  mouth and check to see if a goal can be scored if the ball was to be
     *  kicked in that direction with the given power. If a possible shot is
     *  found, the function will immediately return true, with the target
     *  position stored in the vector ShotTarget.
     * returns true if player has a clean shot at the goal and sets ShotTarget
     * to a normalized vector pointing in the direction the shot should be
     * made. Else returns false and sets heading to a zero vector
     *
     * @param Vector2D $BallPos
     * @param float $power
     * @param Vector2D $ShotTarget
     *
     * @return bool
     */
    public function canShoot(Vector2D $BallPos, $power, Vector2D $ShotTarget = null)
    {
        if ($ShotTarget === null) {
            $ShotTarget = new Vector2D();
        }
        //the number of randomly created shot targets this method will test 
        $NumAttempts = Prm::NumAttemptsToFindValidStrike;

        while ($NumAttempts-- > 0) {
            //choose a random position along the opponent's goal mouth. (making
            //sure the ball's radius is taken into account)
            $ShotTarget->set($this->getOpponentsGoal()->getCenter());

            //the y value of the shot position should lay somewhere between two
            //goalposts (taking into consideration the ball diameter)
            $MinYVal = (int)($this->getOpponentsGoal()->getLeftPost()->y + $this->getPitch()->getBall()->getBoundingRadius());
            $MaxYVal = (int)($this->getOpponentsGoal()->getRightPost()->y - $this->getPitch()
                    ->getBall()
                    ->getBoundingRadius());

            $ShotTarget->y = rand($MinYVal, $MaxYVal);

            //make sure striking the ball with the given power is enough to drive
            //the ball over the goal line.
            $time = $this->getPitch()->getBall()->getTimeToCoverDistance($BallPos,
                $ShotTarget,
                $power);

            //if it is, this shot is then tested to see if any of the opponents
            //can intercept it.
            if ($time >= 0) {
                if ($this->isPassSafeFromAllOpponents($BallPos, $ShotTarget, $power)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The best pass is considered to be the pass that cannot be intercepted
     * by an opponent and that is as far forward of the receiver as possible
     * If a pass is found, the receiver's address is returned in the
     * reference, 'receiver' and the position the pass will be made to is
     * returned in the  reference 'PassTarget'
     *
     * @param PlayerBase $passer
     * @param Vector2D $PassTarget
     * @param float $power
     * @param float $MinPassingDistance
     *
     * @return bool
     */
    public function findPass(PlayerBase $passer, Vector2D $PassTarget, $power, $MinPassingDistance)
    {
        $ClosestToGoalSoFar = null;
        $Target = new Vector2D();

        $found = false;
        $receiver = null;
        //iterate through all this player's team members and calculate which
        //one is in a position to be passed the ball
        foreach ($this->getPlayers() as $player) {
            //make sure the potential receiver being examined is not this player
            //and that it is further away than the minimum pass distance
            if (($player != $passer) && (Vector2D::vectorDistanceSquared($passer->getPosition(),
                        $player->getPosition()) > $MinPassingDistance * $MinPassingDistance)
            ) {
                if ($this->getBestPassToReceiver($player, $Target, $power)) {
                    //if the pass target is the closest to the opponent's goal line found
                    // so far, keep a record of it
                    $Dist2Goal = abs($Target->x - $this->getOpponentsGoal()->getCenter()->x);

                    if ($ClosestToGoalSoFar === null || $Dist2Goal < $ClosestToGoalSoFar) {
                        $ClosestToGoalSoFar = $Dist2Goal;

                        //keep a record of this player
                        $receiver = $player;

                        //and the target
                        $PassTarget->set($Target);

                        $found = true;
                    }
                }
            }
        }

        return array('receiver' => $receiver, 'found' => $found);
    }

    /**
     *  Three potential passes are calculated. One directly toward the receiver's
     *  current position and two that are the tangents from the ball position
     *  to the circle of radius 'range' from the receiver.
     *  These passes are then tested to see if they can be intercepted by an
     *  opponent and to make sure they terminate within the playing area. If
     *  all the passes are invalidated the function returns false. Otherwise
     *  the function returns the pass that takes the ball closest to the
     *  opponent's goal area.
     *
     * @param PlayerBase $receiver
     * @param Vector2D $PassTarget
     * @param float $power
     *
     * @return bool
     */
    public function getBestPassToReceiver(PlayerBase $receiver, Vector2D $PassTarget, $power)
    {
        //first, calculate how much time it will take for the ball to reach
        //this receiver, if the receiver was to remain motionless 
        $time = $this->getPitch()->getBall()->getTimeToCoverDistance($this->getPitch()->getBall()->getPosition(),
            $receiver->getPosition(), $power);

        //return false if ball cannot reach the receiver after having been
        //kicked with the given power
        if ($time < 0) {
            return false;
        }

        //the maximum distance the receiver can cover in this time
        $InterceptRange = $time * $receiver->getMaxSpeed();

        //Scale the intercept range
        $ScalingFactor = 0.3;
        $InterceptRange *= $ScalingFactor;

        //now calculate the pass targets which are positioned at the intercepts
        //of the tangents from the ball to the receiver's range circle.
        $ip1 = new Vector2D();
        $ip2 = new Vector2D();

        Geometry::getTangentPoints($receiver->getPosition(),
            $InterceptRange,
            $this->getPitch()->getBall()->getPosition(),
            $ip1,
            $ip2);

        $Passes = array($ip1, $receiver->getPosition(), $ip2);

        // this pass is the best found so far if it is:
        //
        //  1. Further upfield than the closest valid pass for this receiver
        //     found so far
        //  2. Within the playing area
        //  3. Cannot be intercepted by any opponents

        $ClosestSoFar = null;
        $bResult = false;

        for ($pass = 0; $pass < count($Passes); ++$pass) {
            $dist = abs($Passes[$pass]->x - $this->getOpponentsGoal()->getCenter()->x);

            if (($ClosestSoFar === null || $dist < $ClosestSoFar) &&
                $this->getPitch()->getPlayingArea()->isInside($Passes[$pass]) &&
                $this->isPassSafeFromAllOpponents($this->getPitch()->getBall()->getPosition(), $Passes[$pass], $power,
                    $receiver)
            ) {
                $ClosestSoFar = $dist;
                $PassTarget->set($Passes[$pass]);
                $bResult = true;
            }
        }

        return $bResult;
    }

    /**
     * test if a pass from positions 'from' to 'target' kicked with force
     * 'PassingForce'can be intercepted by an opposing player
     *
     * @param Vector2D $from
     * @param Vector2D $target
     * @param PlayerBase $opponentPlayer
     * @param float $PassingForce
     * @param PlayerBase $receiver
     *
     * @return bool
     */
    public function isPassSafeFromOpponent(
        Vector2D $from,
        Vector2D $target,
        PlayerBase $opponentPlayer,
        $PassingForce,
        PlayerBase $receiver = null
    ) {
        //move the opponent into local space.
        $ToTarget = Vector2D::staticSub($target, $from);
        $ToTargetNormalized = Vector2D::vectorNormalize($ToTarget);

        $LocalPosOpp = Transformation::PointToLocalSpace($opponentPlayer->getPosition(),
            $ToTargetNormalized,
            $ToTargetNormalized->getPerpendicular(),
            $from);

        //if opponent is behind the kicker then pass is considered okay(this is 
        //based on the assumption that the ball is going to be kicked with a 
        //velocity greater than the opponent's max velocity)
        if ($LocalPosOpp->x < 0) {
            return true;
        }

        //if the opponent is further away than the target we need to consider if
        //the opponent can reach the position before the receiver.
        if ($from->distanceTo($target) < $from->distanceTo($opponentPlayer->getPosition())) {
            if ($receiver != null) {
                return $opponentPlayer->distanceTo($target) > $receiver->distanceTo($target);
            } else {
                return true;
            }
        }

        //calculate how long it takes the ball to cover the distance to the 
        //position orthogonal to the opponents position
        $TimeForBall =
            $this->getPitch()->getBall()->getTimeToCoverDistance(new Vector2D(0, 0),
                new Vector2D($LocalPosOpp->x, 0),
                $PassingForce);

        //now calculate how far the opponent can run in this time
        $reach = $opponentPlayer->getMaxSpeed() * $TimeForBall
            + $this->getPitch()->getBall()->getBoundingRadius()
            + $opponentPlayer->getBoundingRadius();

        //if the distance to the opponent's y position is less than his running
        //range plus the radius of the ball and the opponents radius then the
        //ball can be intercepted
        if (abs($LocalPosOpp->y) < $reach) {
            return false;
        }

        return true;
    }

    /**
     * tests a pass from position 'from' to position 'target' against each member
     * of the opposing team. Returns true if the pass can be made without
     * getting intercepted
     *
     * @param Vector2D $from
     * @param Vector2D $target
     * @param float $passingForce
     * @param PlayerBase $receivingPlayer
     *
     * @return bool
     */
    public function isPassSafeFromAllOpponents(
        Vector2D $from,
        Vector2D $target,
        $passingForce,
        PlayerBase $receivingPlayer = null
    ) {

        foreach ($this->getOpponent()->getPlayers() as $opponentPlayer) {
            if (!$this->isPassSafeFromOpponent($from, $target, $opponentPlayer, $passingForce, $receivingPlayer)) {
                return false;
            }
        }

        return true;
    }

    /**
     * returns true if an opposing player is within the radius of the position
     * given as a par ameter
     *
     * @param Vector2D $position
     * @param float $radius
     *
     * @return bool
     */
    public function isOpponentWithinRadius(Vector2D $position, $radius)
    {
        /** @var PlayerBase[] $players */
        $players = $this->getOpponent()->getPlayers();
        foreach ($players as $player) {
            if ($player->distanceTo($position) < $radius) {
                return true;
            }
        }

        return false;
    }

    /**
     * this tests to see if a pass is possible between the requester and
     * the controlling player. If it is possible a message is sent to the
     * controlling player to pass the ball asap.
     *
     * @param FieldPlayer $requestingPlayer
     */
    public function requestPass(FieldPlayer $requestingPlayer)
    {
        //maybe put a restriction here
        if (rand(0, 10) > 1) {
            return;
        }

        if ($this->isPassSafeFromAllOpponents($this->getControllingPlayer()->getPosition(),
            $requestingPlayer->getPosition(),
            Prm::MaxPassingForce, $requestingPlayer)
        ) {
            //tell the player to make the pass
            //let the receiver know a pass is coming 
            MessageDispatcher::getInstance()->dispatch($requestingPlayer, $this->getControllingPlayer(),
                new MessageTypes(MessageTypes::Msg_PassToMe), $requestingPlayer);
        }
    }

    /**
     * calculate the closest player to the SupportSpot
     */
    public function determineBestSupportingAttacker()
    {
        $ClosestSoFar = null;

        $BestPlayer = null;

        foreach ($this->fieldPlayers as $player) {
            //only attackers utilize the BestSupportingSpot
            if (($player->getRole() == FieldPlayer::PLAYER_ROLE_ATTACKER) && !$player->isControllingPlayer()) {
                //calculate the dist. Use the squared value to avoid sqrt
                $dist = Vector2D::vectorDistanceSquared($player->getPosition(),
                    $this->supportSpotCalculator->GetBestSupportingSpot());

                //if the distance is the closest so far and the player is not a
                //goalkeeper and the player is not the one currently controlling
                //the ball, keep a record of this player
                if ($ClosestSoFar == null || $dist < $ClosestSoFar) {
                    $ClosestSoFar = $dist;
                    $BestPlayer = $player;
                }
            }
        }

        return $BestPlayer;
    }

    /**
     * @return StateMachine
     */
    public function getStateMachine()
    {
        return $this->stateMachine;
    }

    public function getHomeGoal()
    {
        return $this->homeGoal;
    }

    public function getOpponentsGoal()
    {
        return $this->opponentsGoal;
    }

    public function getPitch()
    {
        return $this->pitch;
    }

    public function getOpponent()
    {
        return $this->opponent;
    }

    public function SetOpponent(SoccerTeam $opps)
    {
        $this->opponent = $opps;
    }

    public function getColor()
    {
        return $this->color;
    }

    public function resetPlayerClosestToBall()
    {
        $this->playerClosestToBall = null;
    }

    public function getPlayerClosestToBall()
    {
        return $this->playerClosestToBall;
    }

    public function getClosestDistanceToBallSquared()
    {
        return $this->distanceToBallOfClosestPlayerSquared;
    }

    public function getSupportSpot()
    {
        return Vector2D::createByVector2D($this->supportSpotCalculator->GetBestSupportingSpot());
    }

    public function getSupportingPlayer()
    {
        return $this->supportingPlayer;
    }

    public function setSupportingPlayer(PlayerBase $plyr)
    {
        $this->supportingPlayer = $plyr;
    }

    public function resetSupportingPlayer()
    {
        $this->supportingPlayer = null;
    }

    public function getReceiver()
    {
        return $this->receivingPlayer;
    }

    public function setReceiver(PlayerBase $plyr)
    {
        $this->receivingPlayer = $plyr;
    }

    public function resetReceiver()
    {
        $this->receivingPlayer = null;
    }

    public function getControllingPlayer()
    {
        return $this->controllingPlayer;
    }

    public function setControllingPlayer(PlayerBase $player)
    {
        $this->controllingPlayer = $player;
        $this->getOpponent()->setLostControl();
    }

    public function resetControllingPlayer()
    {
        $this->controllingPlayer = null;
        $this->getOpponent()->setLostControl();
    }

    /**
     * @return bool
     */
    public function isInControl()
    {
        return $this->controllingPlayer != null;
    }

    public function setLostControl()
    {
        $this->controllingPlayer = null;
    }

    public function determineBestSupportingPosition()
    {
        $this->supportSpotCalculator->DetermineBestSupportingPosition();
    }

    public function updateTargetsOfWaitingPlayers()
    {
        foreach ($this->fieldPlayers as $player) {
            if ($player->getStateMachine()->isInState(Wait::getInstance())
                || $player->getStateMachine()->isInState(ReturnToHomeRegion::getInstance())
            ) {
                $player->getSteering()->setTarget($player->getHomeRegion()->getCenter());
            }
        }
    }

    /**
     * @return bool
     */
    public function allPlayersAtHome()
    {
        foreach ($this->getPlayers() as $player) {
            if (!$player->isInHomeRegion()) {
                return false;
            }
        }

        return true;
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

    public function jsonSerialize()
    {
        return [
            'players' => $this->getPlayers(),
            'inControl' => $this->isInControl(),
            'state' => $this->stateMachine->getNameOfCurrentState(),
            'supportSpots' => $this->supportSpotCalculator->getSupportSpots(),
            'controllingPlayer' => $this->controllingPlayer,
            'debug' => $this->getDebugMessages(),
        ];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return join('', array_slice(explode('\\', get_class($this)), -1)) . ' ' . $this->color;
    }
}
