<?php

namespace SoccerSimulation\Simulation;

use SoccerSimulation\Common\D2\Geometry;
use SoccerSimulation\Common\D2\Transformation;
use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\StateMachine;
use SoccerSimulation\Common\Game\EntityManager;
use SoccerSimulation\Common\Game\Region;
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
class SoccerTeam implements \JsonSerializable
{
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
     * @var PlayerBase[]
     *
     * pointers to the team members
     */
    private $players = array();

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
     * called each frame. Sets m_pClosestPlayerToBall to point to the player
     * closest to the ball.
     */
    private function CalculateClosestPlayerToBall()
    {
        $ClosestSoFar = null;

        foreach ($this->players as $cur)
        {
            //calculate the dist. Use the squared value to avoid sqrt
            $dist = Vector2D::vectorDistanceSquared($cur->getPosition(), $this->getPitch()->getBall()->getPosition());

            //keep a record of this value for each player
            $cur->setDistanceToBallSquared($dist);

            if ($ClosestSoFar === null || $dist < $ClosestSoFar)
            {
                $ClosestSoFar = $dist;

                $this->playerClosestToBall = $cur;
            }
        }

        $this->distanceToBallOfClosestPlayerSquared = $ClosestSoFar;
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
        $this->stateMachine = new StateMachine($this);
        $this->stateMachine->setCurrentState(Defending::getInstance());
        $this->stateMachine->setPreviousState(Defending::getInstance());
        $this->stateMachine->setGlobalState(null);

        //create the players and goalkeeper
        $this->createPlayers();

        foreach ($this->players as $player)
        {
            $player->getSteering()->activateSeparation();
        }

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

        if ($this->Color() == self::COLOR_RED)
        {
            $this->players[] = $goalKeeperFactory->create($this, 80);
            $this->players[] = $fieldPlayerFactory->create($this, 75, PlayerBase::PLAYER_ROLE_DEFENDER); // LV
            $this->players[] = $fieldPlayerFactory->create($this, 74, PlayerBase::PLAYER_ROLE_DEFENDER); // RV
            $this->players[] = $fieldPlayerFactory->create($this, 72, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $this->players[] = $fieldPlayerFactory->create($this, 71, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $this->players[] = $fieldPlayerFactory->create($this, 59, PlayerBase::PLAYER_ROLE_DEFENDER); // DM
            $this->players[] = $fieldPlayerFactory->create($this, 61, PlayerBase::PLAYER_ROLE_ATTACKER); // LM
            $this->players[] = $fieldPlayerFactory->create($this, 57, PlayerBase::PLAYER_ROLE_ATTACKER); // RM
            $this->players[] = $fieldPlayerFactory->create($this, 52, PlayerBase::PLAYER_ROLE_ATTACKER); // OM
            $this->players[] = $fieldPlayerFactory->create($this, 44, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
            $this->players[] = $fieldPlayerFactory->create($this, 46, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
        }
        else
        {
            $this->players[] = $goalKeeperFactory->create($this, 3);
            $this->players[] = $fieldPlayerFactory->create($this, 8, PlayerBase::PLAYER_ROLE_DEFENDER); // LV
            $this->players[] = $fieldPlayerFactory->create($this, 9, PlayerBase::PLAYER_ROLE_DEFENDER); // RV
            $this->players[] = $fieldPlayerFactory->create($this, 11, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $this->players[] = $fieldPlayerFactory->create($this, 12, PlayerBase::PLAYER_ROLE_DEFENDER); // IV
            $this->players[] = $fieldPlayerFactory->create($this, 24, PlayerBase::PLAYER_ROLE_DEFENDER); // DM
            $this->players[] = $fieldPlayerFactory->create($this, 22, PlayerBase::PLAYER_ROLE_ATTACKER); // LM
            $this->players[] = $fieldPlayerFactory->create($this, 26, PlayerBase::PLAYER_ROLE_ATTACKER); // RM
            $this->players[] = $fieldPlayerFactory->create($this, 31, PlayerBase::PLAYER_ROLE_ATTACKER); // OM
            $this->players[] = $fieldPlayerFactory->create($this, 37, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
            $this->players[] = $fieldPlayerFactory->create($this, 39, PlayerBase::PLAYER_ROLE_ATTACKER); // MS
        }

        foreach ($this->players as $player)
        {
            EntityManager::getInstance()->RegisterEntity($player);
        }
    }

    /**
     *  iterates through each player's update function and calculates
     *  frequently accessed info
     */
    public function update()
    {
        //this information is used frequently so it's more efficient to 
        //calculate it just once each frame
        $this->CalculateClosestPlayerToBall();

        //the team state machine switches between attack/defense behavior. It
        //also handles the 'kick off' state where a team must return to their
        //kick off positions before the whistle is blown
        $this->stateMachine->update();

        //now update each player
        foreach ($this->players as $player)
        {
            $player->update();
        }
    }

    /**
     * calling this changes the state of all field players to that of
     * ReturnToHomeRegion. Mainly used when a goal keeper has
     * possession
     */
    public function returnAllFieldPlayersToHome()
    {
        foreach ($this->players as $cur)
        {
            if ($cur->getRole() != PlayerBase::PLAYER_ROLE_GOALKEEPER)
            {
                MessageDispatcher::getInstance()->dispatch(1, $cur->getId(), new MessageTypes(MessageTypes::Msg_GoHome), null);
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
     */
    public function canShoot(Vector2D $BallPos, $power, Vector2D $ShotTarget = null)
    {
        if ($ShotTarget === null)
        {
            $ShotTarget = new Vector2D();
        }
        //the number of randomly created shot targets this method will test 
        $NumAttempts = Prm::NumAttemptsToFindValidStrike;

        while ($NumAttempts-- > 0)
        {
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
            if ($time >= 0)
            {
                if ($this->isPassSafeFromAllOpponents($BallPos, $ShotTarget, $power))
                {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param PlayerBase $passer
     * @param Vector2D $PassTarget
     * @param float $power
     * @param float $MinPassingDistance
     *
     * @return bool
     *
     * The best pass is considered to be the pass that cannot be intercepted
     * by an opponent and that is as far forward of the receiver as possible
     * If a pass is found, the receiver's address is returned in the
     * reference, 'receiver' and the position the pass will be made to is
     * returned in the  reference 'PassTarget'
     */
    public function findPass(PlayerBase $passer, Vector2D $PassTarget, $power, $MinPassingDistance)
    {
        $ClosestToGoalSoFar = null;
        $Target = new Vector2D();

        $found = false;
        $receiver = null;
        //iterate through all this player's team members and calculate which
        //one is in a position to be passed the ball
        foreach ($this->players as $curPlyr)
        {
            //make sure the potential receiver being examined is not this player
            //and that it is further away than the minimum pass distance
            if (($curPlyr != $passer) && (Vector2D::vectorDistanceSquared($passer->getPosition(), $curPlyr->getPosition()) > $MinPassingDistance * $MinPassingDistance))
            {
                if ($this->getBestPassToReceiver($passer, $curPlyr, $Target, $power))
                {
                    //if the pass target is the closest to the opponent's goal line found
                    // so far, keep a record of it
                    $Dist2Goal = abs($Target->x - $this->getOpponentsGoal()->getCenter()->x);

                    if ($ClosestToGoalSoFar === null || $Dist2Goal < $ClosestToGoalSoFar)
                    {
                        $ClosestToGoalSoFar = $Dist2Goal;

                        //keep a record of this player
                        $receiver = $curPlyr;

                        //and the target
                        $PassTarget->set($Target);

                        $found = true;
                    }
                }
            }
        }

        //next team member

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
     */
    public function getBestPassToReceiver(PlayerBase $passer, PlayerBase $receiver, Vector2D $PassTarget, $power)
    {
        //first, calculate how much time it will take for the ball to reach
        //this receiver, if the receiver was to remain motionless 
        $time = $this->getPitch()->getBall()->getTimeToCoverDistance($this->getPitch()->getBall()->getPosition(), $receiver->getPosition(), $power);

        //return false if ball cannot reach the receiver after having been
        //kicked with the given power
        if ($time < 0)
        {
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

        for ($pass = 0; $pass < count($Passes); ++$pass)
        {
            $dist = abs($Passes[$pass]->x - $this->getOpponentsGoal()->getCenter()->x);

            if (($ClosestSoFar === null || $dist < $ClosestSoFar) &&
                $this->getPitch()->getPlayingArea()->isInside($Passes[$pass]) &&
                $this->isPassSafeFromAllOpponents($this->getPitch()->getBall()->getPosition(), $Passes[$pass], $power, $receiver)
            )
            {
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
     */
    public function isPassSafeFromOpponent(Vector2D $from,
        Vector2D $target,
        PlayerBase $opp,
        $PassingForce, PlayerBase $receiver = null)
    {
        //move the opponent into local space.
        $ToTarget = Vector2D::staticSub($target, $from);
        $ToTargetNormalized = Vector2D::vectorNormalize($ToTarget);

        $LocalPosOpp = Transformation::PointToLocalSpace($opp->getPosition(),
            $ToTargetNormalized,
            $ToTargetNormalized->getPerpendicular(),
            $from);

        //if opponent is behind the kicker then pass is considered okay(this is 
        //based on the assumption that the ball is going to be kicked with a 
        //velocity greater than the opponent's max velocity)
        if ($LocalPosOpp->x < 0)
        {
            return true;
        }

        //if the opponent is further away than the target we need to consider if
        //the opponent can reach the position before the receiver.
        if (Vector2D::vectorDistanceSquared($from, $target) < Vector2D::vectorDistanceSquared($opp->getPosition(), $from))
        {
            if ($receiver != null)
            {
                return Vector2D::vectorDistanceSquared($target, $opp->getPosition()) > Vector2D::vectorDistanceSquared($target, $receiver->getPosition());
            }
            else
            {
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
        $reach = $opp->getMaxSpeed() * $TimeForBall
            + $this->getPitch()->getBall()->getBoundingRadius()
            + $opp->getBoundingRadius();

        //if the distance to the opponent's y position is less than his running
        //range plus the radius of the ball and the opponents radius then the
        //ball can be intercepted
        if (abs($LocalPosOpp->y) < $reach)
        {
            return false;
        }

        return true;
    }

    /**
     * tests a pass from position 'from' to position 'target' against each member
     * of the opposing team. Returns true if the pass can be made without
     * getting intercepted
     */
    public function isPassSafeFromAllOpponents(Vector2D $from,
        Vector2D $target,
        $PassingForce,
        PlayerBase $receiver = null)
    {

        foreach ($this->getOpponent()->getMembers() as $opp)
        {
            if (!$this->isPassSafeFromOpponent($from, $target, $opp, $PassingForce, $receiver))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * returns true if an opposing player is within the radius of the position
     * given as a par ameter
     */
    public function isOpponentWithinRadius(Vector2D $pos, $rad)
    {
        /** @var PlayerBase[] $members */
        $members = $this->getOpponent()->getMembers();
        foreach ($members as $it)
        {
            if (Vector2D::vectorDistanceSquared($pos, $it->getPosition()) < $rad * $rad)
            {
                return true;
            }
        }

        return false;
    }

    /**
     * this tests to see if a pass is possible between the requester and
     * the controlling player. If it is possible a message is sent to the
     * controlling player to pass the ball asap.
     */
    public function requestPass(FieldPlayer $requester)
    {
        //maybe put a restriction here
        if (rand(0, 10) > 1)
        {
            return;
        }

        if ($this->isPassSafeFromAllOpponents($this->getControllingPlayer()->getPosition(),
            $requester->getPosition(),
            Prm::MaxPassingForce, $requester)
        )
        {

            //tell the player to make the pass
            //let the receiver know a pass is coming 
            MessageDispatcher::getInstance()->dispatch($requester->getId(), $this->getControllingPlayer()->getId(), new MessageTypes(MessageTypes::Msg_PassToMe), $requester);

        }
    }

    /**
     * calculate the closest player to the SupportSpot
     */
    public function determineBestSupportingAttacker()
    {
        $ClosestSoFar = null;

        $BestPlayer = null;

        foreach ($this->players as $cur)
        {
            //only attackers utilize the BestSupportingSpot
            if (($cur->getRole() == PlayerBase::PLAYER_ROLE_ATTACKER) && ($cur != $this->controllingPlayer))
            {
                //calculate the dist. Use the squared value to avoid sqrt
                $dist = Vector2D::vectorDistanceSquared($cur->getPosition(), $this->supportSpotCalculator->GetBestSupportingSpot());

                //if the distance is the closest so far and the player is not a
                //goalkeeper and the player is not the one currently controlling
                //the ball, keep a record of this player
                if ($ClosestSoFar == null || $dist < $ClosestSoFar)
                {
                    $ClosestSoFar = $dist;
                    $BestPlayer = $cur;
                }
            }
        }

        return $BestPlayer;
    }

    /**
     * @return PlayerBase
     */
    public function getMembers()
    {
        return $this->players;
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

    public function Color()
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

    public function setPlayerHomeRegion($plyr, $region)
    {
        $this->players[$plyr]->setHomeRegion($region);
    }

    public function determineBestSupportingPosition()
    {
        $this->supportSpotCalculator->DetermineBestSupportingPosition();
    }

    public function updateTargetsOfWaitingPlayers()
    {
        foreach ($this->players as $cur)
        {
            if ($cur->getRole() != PlayerBase::PLAYER_ROLE_GOALKEEPER)
            {
                //cast to a field player
                /** @var FieldPlayer $plyr */
                $plyr = $cur;

                if ($plyr->getStateMachine()->isInState(Wait::getInstance())
                    || $plyr->getStateMachine()->isInState(ReturnToHomeRegion::getInstance())
                )
                {
                    $plyr->getSteering()->setTarget($plyr->getHomeRegion()->getCenter());
                }
            }
        }
    }

    /**
     * @return false if any of the team are not located within their home region
     */
    public function allPlayersAtHome()
    {
        foreach ($this->players as $it)
        {
            if ($it->isInHomeRegion() == false)
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->color == self::COLOR_BLUE ? 'Blue' : 'Red';
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
            'players' => $this->players,
            'inControl' => $this->isInControl(),
            'state' => $this->stateMachine->getNameOfCurrentState(),
            'supportSpots' => $this->supportSpotCalculator->getSupportSpots(),
            'controllingPlayer' => $this->controllingPlayer,
            'debug' => $this->getDebugMessages(),
        ];
    }

    /**
     *  renders the players and any team related info
     */
    public function render()
    {
        throw new \Exception('dont use render');
    }
}
