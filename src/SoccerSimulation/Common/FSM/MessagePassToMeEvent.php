<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Simulation\Nameable;
use Symfony\Component\EventDispatcher\Event;

class MessagePassToMeEvent extends Event
{
    /**
     * @var Nameable
     */
    private $passingPlayer;

    /**
     * @var Nameable
     */
    private $requestingPlayer;

    /**
     * @var bool
     */
    private $passIsExecuted;

    /**
     * @param Nameable $passingPlayer
     * @param Nameable $requestingPlayer
     * @param bool $passIsExecuted
     */
    public function __construct(Nameable $passingPlayer, Nameable $requestingPlayer, $passIsExecuted)
    {
        $this->passingPlayer = $passingPlayer;
        $this->requestingPlayer = $requestingPlayer;
        $this->passIsExecuted = $passIsExecuted;
    }

    /**
     * @return Nameable
     */
    public function getPassingPlayer()
    {
        return $this->passingPlayer;
    }

    /**
     * @return Nameable
     */
    public function getRequestingPlayer()
    {
        return $this->requestingPlayer;
    }

    /**
     * @return mixed
     */
    public function getPassIsExecuted()
    {
        return $this->passIsExecuted;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'soccer_simulation.message.pass_to_me';
    }
}
