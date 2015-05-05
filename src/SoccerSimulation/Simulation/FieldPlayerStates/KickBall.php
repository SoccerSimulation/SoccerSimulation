<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\CannotKickBallEvent;
use SoccerSimulation\Common\FSM\EnterStateEvent;
use SoccerSimulation\Common\FSM\PassEvent;
use SoccerSimulation\Common\FSM\ShotEvent;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\MessageDispatcher;
use SoccerSimulation\Simulation\FieldPlayer;
use SoccerSimulation\Simulation\MessageTypes;
use SoccerSimulation\Simulation\PlayerBase;
use SoccerSimulation\Simulation\Prm;
use SoccerSimulation\Simulation\SoccerBall;

class KickBall extends State
{
    /**
     * @var KickBall
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new KickBall();
        }

        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player)
    {
        //let the team know this player is controlling
        $player->getTeam()->setControllingPlayer($player);

        //the player can only make so many kick attempts per second.
        if (!$player->isReadyForNextKick()) {
            $player->getStateMachine()->changeState(ChaseBall::getInstance());
        }

        $this->raise(new EnterStateEvent($this, $player));
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player)
    {
        if ($player->getPitch()->hasGoalKeeperBall()) {
            $this->raise(new CannotKickBallEvent($player, 'goalkeeper has the ball'));
            $player->getStateMachine()->changeState(ChaseBall::getInstance());

            return;
        }

        if ($player->getTeam()->getReceiver() != null) {
            $this->raise(new CannotKickBallEvent($player, 'already defined a receiver'));
            $player->getStateMachine()->changeState(ChaseBall::getInstance());

            return;
        }

        if (!$player->isBallAhead()) {
            $this->raise(new CannotKickBallEvent($player, 'ball is behind player'));
            $player->getStateMachine()->changeState(ChaseBall::getInstance());

            return;
        }

        /* Attempt a shot at the goal */

        //if a shot is possible, this vector will hold the position along the 
        //opponent's goal line the player should aim for.
        $ballTarget = new Vector2D();

        //the dot product is used to adjust the shooting force. The more
        //directly the ball is ahead, the more forceful the kick
        $power = $player->getShootingForce();

        //if it is determined that the player could score a goal from this position
        //OR if he should just kick the ball anyway, the player will attempt
        //to make the shot
        if ($player->getTeam()->canShoot($player->getBall()->getPosition(), $power,
                $ballTarget) || (lcg_value() < Prm::ChancePlayerAttemptsPotShot)
        ) {
            $this->shoot($player, $ballTarget, $power);

            return;
        }

        /* Attempt a pass to a player */
        $power = $player->getPassingForce();
        $pass = $player->getTeam()->findPass($player, $ballTarget, $power, Prm::MinPassDist);
        if ($player->isThreatened() && $pass['found']) {
            $this->pass($player, $pass['receiver'], $ballTarget, $power);

            return;
        }

        $player->findSupport();
        $player->getStateMachine()->changeState(Dribble::getInstance());
    }

    /**
     * @param PlayerBase $player
     * @param Vector2D $ballTarget
     * @param float $power
     */
    private function shoot(PlayerBase $player, Vector2D $ballTarget, $power)
    {
        $this->raise(new ShotEvent($player));

        //add some noise to the kick. We don't want players who are
        //too accurate! The amount of noise can be adjusted by altering
        //Prm.PlayerKickingAccuracy
        $ballTarget = SoccerBall::addNoiseToKick($player->getBall()->getPosition(), $ballTarget);

        //this is the direction the ball will be kicked in
        $KickDirection = Vector2D::staticSub($ballTarget, $player->getBall()->getPosition());

        $player->getBall()->kick($KickDirection, $power);

        //change state
        $player->getStateMachine()->changeState(Wait::getInstance());

        $player->findSupport();
    }

    /**
     * @param PlayerBase $player
     * @param PlayerBase $receiver
     * @param Vector2D $ballTarget
     * @param float $power
     */
    private function pass(PlayerBase $player, PlayerBase $receiver, Vector2D $ballTarget, $power)
    {
        /** @var PlayerBase $r */
        $r = clone $receiver;
        //add some noise to the kick
        $ballTarget = SoccerBall::addNoiseToKick($player->getBall()->getPosition(), $ballTarget);

        $KickDirection = Vector2D::staticSub($ballTarget, $player->getBall()->getPosition());

        $player->getBall()->kick($KickDirection, $power);

        $this->raise(new PassEvent($player, $r));

        //let the receiver know a pass is coming
        MessageDispatcher::getInstance()->dispatch($player, $r, new MessageTypes(MessageTypes::Msg_ReceiveBall),
            $ballTarget);

        //the player should wait at his current position unless instruced
        //otherwise
        $player->getStateMachine()->changeState(Wait::getInstance());

        $player->findSupport();
    }
}
