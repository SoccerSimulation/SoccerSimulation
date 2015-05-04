<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Simulation\Nameable;
use Symfony\Component\EventDispatcher\Event;

class EnterStateEvent extends Event
{
    /**
     * @var State
     */
    private $state;

    /**
     * @var Nameable
     */
    private $owner;

    /**
     * @param State $state
     * @param Nameable $owner
     */
    public function __construct(State $state, Nameable $owner)
    {
        $this->state = $state;
        $this->owner = $owner;
    }

    /**
     * @return State
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return Nameable
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'soccer_simulation.state.enter';
    }
}
