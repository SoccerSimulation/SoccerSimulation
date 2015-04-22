<?php

namespace SoccerSimulation\Common\Messaging;

use SoccerSimulation\Common\Game\EntityManager;
use SoccerSimulation\Simulation\BaseGameEntity;
use SoccerSimulation\Simulation\MessageTypes;

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

    /**
     * this method is utilized by DispatchMsg or DispatchDelayedMessages.
     * This method calls the message handling member function of the receiving
     * entity, pReceiver, with the newly created telegram
     */
    private function Discharge(BaseGameEntity $pReceiver, Telegram $telegram) {
        if (!$pReceiver->handleMessage($telegram)) {
            //telegram could not be handled
            if (self::SHOW_MESSAGING_INFO) {
                echo "Message not handled\n";
            }
        }
    }

    //this class is a singleton
    public static function getInstance() {
        if (self::$Dispatcher === null) {
            self::$Dispatcher = new MessageDispatcher();
        }
        return self::$Dispatcher;
    }

    /**
     * given a message, a receiver, a sender and any time delay, this function
     * routes the message to the correct agent (if no delay) or stores
     * in the message queue to be dispatched at the correct time
     */
    public function dispatch($sender, $receiver, MessageTypes $message, $additionalInfo = null) {
        //get a pointer to the receiver
        $pReceiver = EntityManager::getInstance()->getEntityFromId($receiver);

        //make sure the receiver is valid
        if ($pReceiver == null) {
            if (self::SHOW_MESSAGING_INFO) {
                echo "Warning! No Receiver with ID of " . $receiver . " found\n";
            }

            return;
        }

        //create the telegram
        $telegram = new Telegram($sender, $receiver, $message, $additionalInfo);

        if (self::SHOW_MESSAGING_INFO) {
            echo "Telegram dispatched by " . $sender . " for " . $receiver . ". Message is " . $message . "\n";
        }
        //send the telegram to the recipient
        $this->Discharge($pReceiver, $telegram);
    }
}
