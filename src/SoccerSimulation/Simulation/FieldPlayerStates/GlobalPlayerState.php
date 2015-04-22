<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\State;
use SoccerSimulation\Common\Messaging\MessageDispatcher;
use SoccerSimulation\Common\Messaging\Telegram;
use SoccerSimulation\Simulation\Define;
use SoccerSimulation\Simulation\FieldPlayer;
use SoccerSimulation\Simulation\MessageTypes;
use SoccerSimulation\Simulation\Prm;

class GlobalPlayerState extends State
{
    /**
     * @var GlobalPlayerState
     */
    private static $instance;

    private function __construct()
    {
    }

    //this is a singleton
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new GlobalPlayerState();
        }
        return self::$instance;
    }

    /**
     * @param FieldPlayer $player
     */
    public function enter($player) {
    }

    /**
     * @param FieldPlayer $player
     */
    public function execute($player) {
        //if a player is in possession and close to the ball reduce his max speed
        if (($player->isBallWithinReceivingRange()) && ($player->isControllingPlayer())) {
            $player->setMaxSpeed(Prm::PlayerMaxSpeedWithBall);
        } else {
            $player->setMaxSpeed(Prm::PlayerMaxSpeedWithoutBall);
        }

    }

    /**
     * @param FieldPlayer $player
     */
    public function quit($player) {
    }

    /**
     * @param FieldPlayer $player
     * @param Telegram $telegram
     *
     * @return bool
     */
    public function onMessage($player, Telegram $telegram) {
        switch ($telegram->message->messageType) {
            case MessageTypes::Msg_ReceiveBall: {
                //set the target
                $player->getSteering()->setTarget($telegram->extraInfo);

                //change state 
                $player->getStateMachine()->changeState(ReceiveBall::getInstance());

                return true;
            }
            //break;

            case MessageTypes::Msg_SupportAttacker: {
                //if already supporting just return
                if ($player->getStateMachine()->isInState(SupportAttacker::getInstance())) {
                    return true;
                }

                //set the target to be the best supporting position
                $player->getSteering()->setTarget($player->getTeam()->getSupportSpot());

                //change the state
                $player->getStateMachine()->changeState(SupportAttacker::getInstance());

                return true;
            }

            //break;

            case MessageTypes::Msg_Wait: {
                //change the state
                $player->getStateMachine()->changeState(Wait::getInstance());

                return true;
            }
           // break;

            case MessageTypes::Msg_GoHome: {
                $player->setDefaultHomeRegion();

                $player->getStateMachine()->changeState(ReturnToHomeRegion::getInstance());

                return true;
            }

            // break;

            case MessageTypes::Msg_PassToMe: {
                //get the position of the player requesting the pass
                /** @var FieldPlayer $receiver */
                $receiver = $telegram->extraInfo;

                if (Define::PLAYER_STATE_INFO_ON) {
                    $player->addDebugMessages('Player ' . $player->getId() . ' received request from ' . $receiver->getId() . ' to make pass');
                    echo "Player " . $player->getId() . " received request from " . $receiver->getId() . " to make pass\n";
                }

                //if the ball is not within kicking range or their is already a 
                //receiving player, this player cannot pass the ball to the player
                //making the request.
                if ($player->getTeam()->getReceiver() != null
                        || !$player->isBallWithinKickingRange()) {
                    if (Define::PLAYER_STATE_INFO_ON) {
                        $player->addDebugMessages('Player ' . $player->getId() . ' cannot make requested pass <cannot kick ball>');
                        echo "Player " . $player->getId() . " cannot make requested pass <cannot kick ball>\n";
                    }

                    return true;
                }

                //make the pass   
                $player->getBall()->kick(Vector2D::staticSub($receiver->getPosition(), $player->getBall()->getPosition()),
                        Prm::MaxPassingForce);


                if (Define::PLAYER_STATE_INFO_ON) {
                    echo "Player " . $player->getId() . " passed ball to requesting player\n";
                    $player->addDebugMessages('Player ' . $player->getId() . ' passed ball to requesting player');
                }

                //let the receiver know a pass is coming 
                MessageDispatcher::getInstance()->dispatch($player->getId(),
                        $receiver->getId(),
                        new MessageTypes(MessageTypes::Msg_ReceiveBall),
                        $receiver->getPosition());



                //change state   
                $player->getStateMachine()->changeState(Wait::getInstance());

                $player->findSupport();

                return true;
            }

            //break;

        }//end switch

        return false;
    }
}
