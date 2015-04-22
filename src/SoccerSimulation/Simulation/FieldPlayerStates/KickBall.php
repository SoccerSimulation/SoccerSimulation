<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\MessageDispatcher;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\Define;
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
    public function enter($player) {
        //let the team know this player is controlling
        $player->getTeam()->setControllingPlayer($player);

        //the player can only make so many kick attempts per second.
        if (!$player->isReadyForNextKick()) {
            $player->getStateMachine()->changeState(ChaseBall::getInstance());
        }


        if (Define::PLAYER_STATE_INFO_ON) {
            $player->addDebugMessages('Player ' . $player->getId() . ' enters kick state');
            echo "Player " . $player->getId() . " enters kick state\n";
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player) {
        //calculate the dot product of the vector pointing to the ball
        //and the player's heading
        $ToBall = Vector2D::staticSub($player->getBall()->getPosition(), $player->getPosition());
        $dot = $player->getHeading()->dot(Vector2D::vectorNormalize($ToBall));

        //cannot kick the ball if the goalkeeper is in possession or if it is 
        //behind the player or if there is already an assigned receiver. So just
        //continue chasing the ball
        if ($player->getTeam()->getReceiver() != null
                || $player->getPitch()->hasGoalKeeperBall()
                || ($dot < 0)) {
            if (Define::PLAYER_STATE_INFO_ON) {
                $player->addDebugMessages('Goaly has ball / ball behind player');
                echo "Goaly has ball / ball behind player\n";
            }

            $player->getStateMachine()->changeState(ChaseBall::getInstance());

            return;
        }

        /* Attempt a shot at the goal */

        //if a shot is possible, this vector will hold the position along the 
        //opponent's goal line the player should aim for.
        $ballTarget = new Vector2D();

        //the dot product is used to adjust the shooting force. The more
        //directly the ball is ahead, the more forceful the kick
        $power = Prm::MaxShootingForce * $dot;

        //if it is determined that the player could score a goal from this position
        //OR if he should just kick the ball anyway, the player will attempt
        //to make the shot
        if ($player->getTeam()->canShoot($player->getBall()->getPosition(),
                $power,
                $ballTarget)
                || (lcg_value() < Prm::ChancePlayerAttemptsPotShot)) {
            if (Define::PLAYER_STATE_INFO_ON) {
                $player->addDebugMessages('Player ' . $player->getId() . ' attempts a shot at ' . $ballTarget);
                echo "Player " . $player->getId() . " attempts a shot at " . $ballTarget . "\n";
            }

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

            return;
        }


        /* Attempt a pass to a player */

        //if a receiver is found this will point to it
        $receiver = null;

        $power = Prm::MaxPassingForce * $dot;

        //test if there are any potential candidates available to receive a pass
        $pass = $player->getTeam()->findPass($player, $ballTarget, $power, Prm::MinPassDist);
        if ($player->isThreatened() && $pass['found']) {
            /** @var PlayerBase $receiver */
            $receiver = clone $pass['receiver'];
            //add some noise to the kick
            $ballTarget = SoccerBall::addNoiseToKick($player->getBall()->getPosition(), $ballTarget);

            $KickDirection = Vector2D::staticSub($ballTarget, $player->getBall()->getPosition());

            $player->getBall()->kick($KickDirection, $power);

            if (Define::PLAYER_STATE_INFO_ON) {
                $player->addDebugMessages('Player ' . $player->getId() . ' passes the ball with force ' . $power . ' to player ' . $receiver->getId() . ' Target is ' . $ballTarget);
                echo "Player " . $player->getId() . " passes the ball with force " . $power . "  to player " . $receiver->getId() . "  Target is " . $ballTarget . "\n";
            }


            //let the receiver know a pass is coming 
            MessageDispatcher::getInstance()->dispatch($player->getId(),
                    $receiver->getId(),
                    new MessageTypes(MessageTypes::Msg_ReceiveBall),
                    $ballTarget);


            //the player should wait at his current position unless instruced
            //otherwise  
            $player->getStateMachine()->changeState(Wait::getInstance());

            $player->findSupport();

            return;
        } //cannot shoot or pass, so dribble the ball upfield
        else {
            $player->findSupport();

            $player->getStateMachine()->changeState(Dribble::getInstance());
        }
    }

    /**
     * @param FieldPlayer $player
     */
    public function quit($player) {
    }

    /**
     * @param FieldPlayer $e
     * @param Telegram $t
     *
     * @return bool
     */
    public function onMessage($e, Telegram $t) {
        return false;
    }
}
