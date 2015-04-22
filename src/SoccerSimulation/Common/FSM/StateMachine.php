<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Common\Messaging\Telegram;

/**
 * State machine class. Inherit from this class and create some 
 * states to give your agents FSM functionality
 */
class StateMachine
{
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

    public function __construct($owner)
    {
        $this->owner = $owner;
        $this->currentState = null;
        $this->previousState = null;
        $this->globalState = null;
    }

    //use these methods to initialize the FSM
    public function setCurrentState(State $s) {
        $this->currentState = $s;
    }

    public function setGlobalState(State $s = null) {
        $this->globalState = $s;
    }

    public function setPreviousState(State $s) {
        $this->previousState = $s;
    }

    //call this to update the FSM
    public function update() {
        //if a global state exists, call its execute method, else do nothing
        if ($this->globalState != null) {
            $this->globalState->execute($this->owner);
        }

        //same for the current state
        if ($this->currentState != null) {
            $this->currentState->execute($this->owner);
        }
    }

    public function handleMessage(Telegram $msg) {
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
    public function changeState(State $pNewState) {
        //keep a record of the previous state
        $this->previousState = $this->currentState;

        //call the exit method of the existing state
        $this->currentState->quit($this->owner);

        //change state to the new state
        $this->currentState = $pNewState;

        //call the entry method of the new state
        $this->currentState->enter($this->owner);
    }

    //change state back to the previous state
    public function revertToPreviousState() {
        $this->changeState($this->previousState);
    }

    //returns true if the current state's type is equal to the type of the
    //class passed as a parameter. 
    public function isInState(State $st) {
        return get_class($this->currentState) == get_class($st);
    }

    public function getCurrentState() {
        return $this->currentState;
    }

    public function getGlobalState() {
        return $this->globalState;
    }

    public function getPreviousState() {
        return $this->previousState;
    }
    //only ever used during debugging to grab the name of the current state

    public function getNameOfCurrentState() {
        $s = explode('\\', get_class($this->currentState));
        if(count($s) > 0) {
            return $s[count($s) - 1];
        }
        return get_class($this->currentState);
    }
}