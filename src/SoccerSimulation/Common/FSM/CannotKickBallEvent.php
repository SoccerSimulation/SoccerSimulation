<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Simulation\Nameable;
use Symfony\Component\EventDispatcher\Event;

class CannotKickBallEvent extends Event
{
    /**
     * @var Nameable
     */
    private $player;

    /**
     * @var string
     */
    private $reason;

    /**
     * @param Nameable $player
     * @param string $reason
     */
    public function __construct(Nameable $player, $reason)
    {
        $this->player = $player;
        $this->reason = $reason;
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
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'soccer_simulation.kick.cannot_kick_ball';
    }
}
