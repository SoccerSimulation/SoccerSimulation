<?php

namespace SoccerSimulation\Common\Messaging;

use SoccerSimulation\Simulation\MessageTypes;

class Telegram
{
    /**
     * @var int
     *
     * the entity that sent this telegram
     */
    public $sender;

    /**
     * @var int
     *
     * the entity that is to receive this telegram
     */
    public $receiver;

    /**
     * @var MessageTypes
     *
     * the message itself. These are all enumerated in the file
     * "MessageTypes.h"
     */
    public $message;

    /**
     * @var
     *
     * any additional information that may accompany the message
     */
    public $extraInfo;

    /**
     * @param int $sender
     * @param int $receiver
     * @param $msg
     * @param $info
     */
    public function __construct($sender = -1, $receiver = -1, $msg = null, $info = null) {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->message = $msg;
        $this->extraInfo = $info;
    }

    public function __toString()
    {
        return "Sender: " . $this->sender . "   Receiver: " . $this->receiver . "   Msg: " . $this->message;
    }
}
