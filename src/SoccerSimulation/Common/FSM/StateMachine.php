<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Common\Event\EventGenerator;
use SoccerSimulation\Common\Messaging\Telegram;

/**
 * State machine class. Inherit from this class and create some 
 * states to give your agents FSM functionality
 */
class StateMachine
{
    use EventGenerator;

    //a pointer to the agent that owns this instance
    private $owner;

    /**
     * @var State
     */
    private $currentState;

    /**
     * @var State
     *
     * a record of the last state the agent was in
     */
    private $previousState;

    /**
     * @var State
     *
     * this is called every time the FSM is updated
     */
    private $globalState;

    /**
     * @param mixed $owner
     * @param State|null $currentState
     * @param State|null $previousState
     * @param State|null $globalState
     */
    public function __construct($owner, State $currentState = null, State $previousState = null, State $globalState = null)
    {
        $this->owner = $owner;
        $this->currentState = $currentState;
        $this->previousState = $previousState;
        $this->globalState = $globalState;
    }

    public function update()
    {
        if ($this->globalState != null) {
            $this->globalState->execute($this->owner);
            $this->raiseMultiple($this->globalState->releaseEvents());
        }

        if ($this->currentState != null) {
            $this->currentState->execute($this->owner);
            $this->raiseMultiple($this->currentState->releaseEvents());
        }
    }

    /**
     * @param Telegram $msg
     *
     * @return bool
     */
    public function handleMessage(Telegram $msg)
    {
        //first see if the current state is valid and that it can handle
        //the message
        if ($this->currentState != null && $this->currentState->onMessage($this->owner, $msg)) {
            return true;
        }

        //if not, and if a global state has been implemented, send
        //the message to the global state
        if ($this->globalState != null && $this->globalState->onMessage($this->owner, $msg)) {
            return true;
        }

        return false;
    }

    //change to a new state
    public function changeState(State $pNewState)
    {
        $this->previousState = $this->currentState;
        $this->previousState->quit($this->owner);
        $this->raiseMultiple($this->previousState->releaseEvents());

        $this->currentState = $pNewState;
        $this->currentState->enter($this->owner);
        $this->raiseMultiple($this->currentState->releaseEvents());
    }

    //change state back to the previous state
    public function revertToPreviousState()
    {
        $this->changeState($this->previousState);
        $this->raiseMultiple($this->previousState->releaseEvents());
    }

    //returns true if the current state's type is equal to the type of the
    //class passed as a parameter.
    public function isInState(State $st)
    {
        return get_class($this->currentState) == get_class($st);
    }

    public function getCurrentState()
    {
        return $this->currentState;
    }

    public function getGlobalState()
    {
        return $this->globalState;
    }

    public function getPreviousState()
    {
        return $this->previousState;
    }
    //only ever used during debugging to grab the name of the current state

    public function getNameOfCurrentState()
    {
        $s = explode('\\', get_class($this->currentState));
        if(count($s) > 0) {
            return $s[count($s) - 1];
        }
        return get_class($this->currentState);
    }
}
