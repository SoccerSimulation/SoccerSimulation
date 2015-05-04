<?php

namespace SoccerSimulation\Simulation;

class MessageTypes
{
    const Msg_ReceiveBall = 1;
    const Msg_PassToMe = 2;
    const Msg_SupportAttacker = 3;
    const Msg_GoHome = 4;
    const Msg_Wait = 5;

    /**
     * @var int
     */
    public $messageType;

    public function __construct($messageType)
    {
        $this->messageType = $messageType;
    }

    public function __toString()
    {
        return self::messageToString($this);
    }

    public static function messageToString(MessageTypes $msg)
    {
        switch ($msg->messageType) {
            case self::Msg_ReceiveBall:
                return "Msg_ReceiveBall";

            case self::Msg_PassToMe:
                return "Msg_PassToMe";

            case self::Msg_SupportAttacker:
                return "Msg_SupportAttacker";

            case self::Msg_GoHome:
                return "Msg_GoHome";

            case self::Msg_Wait:
                return "Msg_Wait";

            default:
                return "INVALID MESSAGE!!";
        }
    }
}
