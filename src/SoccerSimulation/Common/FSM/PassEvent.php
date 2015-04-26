<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Simulation\Nameable;
use Symfony\Component\EventDispatcher\Event;

class PassEvent extends Event
{
    /**
     * @var Nameable
     */
    private $player;

    /**
     * @var Nameable
     */
    private $receiver;

    /**
     * @param Nameable $player
     * @param Nameable $receiver
     */
    public function __construct(Nameable $player, Nameable $receiver)
    {
        $this->player = $player;
        $this->receiver = $receiver;
    }

    /**
     * @return Nameable
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return Nameable
     */
    public function getReceiver()
    {
        return $this->receiver;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'soccer_simulation.kick.pass';
    }
}
