<?php

namespace SoccerSimulation\Simulation\FieldPlayerStates;

use SoccerSimulation\Common\D2\Vector2D;
use SoccerSimulation\Common\FSM\MessagePassToMeEvent;
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
     * @param Telegram $telegram
     *
     * @return bool
     */
    public function onMessage($player, Telegram $telegram)
    {
        switch ($telegram->message->messageType) {
            case MessageTypes::Msg_ReceiveBall:
                $player->getSteering()->setTarget($telegram->extraInfo);
                $player->getStateMachine()->changeState(ReceiveBall::getInstance());
                return true;

            case MessageTypes::Msg_SupportAttacker:
                if ($player->getStateMachine()->isInState(SupportAttacker::getInstance())) {
                    return true;
                }
                $player->getSteering()->setTarget($player->getTeam()->getSupportSpot());
                $player->getStateMachine()->changeState(SupportAttacker::getInstance());
                return true;

            case MessageTypes::Msg_Wait:
                $player->getStateMachine()->changeState(Wait::getInstance());
                return true;

            case MessageTypes::Msg_GoHome:
                $player->setDefaultHomeRegion();
                $player->getStateMachine()->changeState(ReturnToHomeRegion::getInstance());
                return true;

            case MessageTypes::Msg_PassToMe:
                //get the position of the player requesting the pass
                /** @var FieldPlayer $receivingPlayer */
                $receivingPlayer = $telegram->extraInfo;

                //if the ball is not within kicking range or their is already a
                //receiving player, this player cannot pass the ball to the player
                //making the request.
                if ($player->getTeam()->getReceiver() != null || !$player->isBallWithinKickingRange()) {
                    if (Define::PLAYER_STATE_INFO_ON) {
                        $this->raise(new MessagePassToMeEvent($player, $receivingPlayer, false));
                    }

                    return true;
                }

                //make the pass   
                $player->getBall()->kick(Vector2D::staticSub($receivingPlayer->getPosition(),
                    $player->getBall()->getPosition()), Prm::MaxPassingForce);


                if (Define::PLAYER_STATE_INFO_ON) {
                    $this->raise(new MessagePassToMeEvent($player, $receivingPlayer, true));
                }

                //let the receiver know a pass is coming 
                MessageDispatcher::getInstance()->dispatch($player, $receivingPlayer,
                    new MessageTypes(MessageTypes::Msg_ReceiveBall), $receivingPlayer->getPosition());

                //change state   
                $player->getStateMachine()->changeState(Wait::getInstance());

                $player->findSupport();

                return true;
        }

        return false;
    }
}
