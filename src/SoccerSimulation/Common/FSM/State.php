<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Common\Messaging\Telegram;

/**
 * abstract base class to define an interface for a state
 */
abstract class State
{
  //this will execute when the state is entered
  abstract public function enter($e);

  //this is the state's normal update function
  abstract public function execute($e);

  //this will execute when the state is exited. (My word, isn't
  //life full of surprises... ;o))
  abstract public function quit($e);
  
  //this executes if the agent receives a message from the 
  //message dispatcher
  abstract public function onMessage($e, Telegram $t);
}
