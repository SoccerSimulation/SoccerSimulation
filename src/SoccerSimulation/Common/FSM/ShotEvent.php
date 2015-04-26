<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Simulation\Nameable;
use Symfony\Component\EventDispatcher\Event;

class ShotEvent extends Event
{
    /**
     * @var Nameable
     */
    private $player;

    /**
     * @param Nameable $player
     */
    public function __construct(Nameable $player)
    {
        $this->player = $player;
    }

    /**
     * @return Nameable
     */
    public function getPlayer()
    {
        return $this->player;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'soccer_simulation.kick.shot';
    }
}
