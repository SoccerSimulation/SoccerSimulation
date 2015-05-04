<?php

namespace SoccerSimulation\Common\Messaging;

use SoccerSimulation\Common\Game\EntityManager;
use SoccerSimulation\Simulation\BaseGameEntity;
use SoccerSimulation\Simulation\MessageTypes;
use SoccerSimulation\Simulation\PlayerBase;

/**
 * Desc:   A message dispatcher. Manages messages of the type Telegram.
 *         Instantiated as a singleton.
 */
class MessageDispatcher
{
    const SHOW_MESSAGING_INFO = false;

    //to make life easier...
    public static $Dispatcher;

    const NO_ADDITIONAL_INFO = 0;
    const SENDER_ID_IRRELEVANT = -1;

    //this class is a singleton
    public static function getInstance()
    {
        if (self::$Dispatcher === null) {
            self::$Dispatcher = new MessageDispatcher();
        }
        return self::$Dispatcher;
    }

    /**
     * given a message, a receiver, a sender and any time delay, this function
     * routes the message to the correct agent (if no delay) or stores
     * in the message queue to be dispatched at the correct time
     *
     * @param PlayerBase $sender
     * @param PlayerBase $receiver
     * @param MessageTypes $message
     * @param mixed $additionalInfo
     */
    public function dispatch(PlayerBase $sender, PlayerBase $receiver, MessageTypes $message, $additionalInfo = null)
    {
        //create the telegram
        $telegram = new Telegram($sender->getId(), $receiver->getId(), $message, $additionalInfo);

        //send the telegram to the recipient
        $receiver->handleMessage($telegram);
    }
}
