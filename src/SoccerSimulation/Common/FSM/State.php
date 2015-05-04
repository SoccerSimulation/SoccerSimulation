<?php

namespace SoccerSimulation\Common\FSM;

use SoccerSimulation\Common\Event\EventGenerator;
use SoccerSimulation\Common\Messaging\Telegram;

abstract class State
{
    use EventGenerator;

    public function enter($owner) {}

    public function execute($owner) {}

    public function quit($entity) {}

    public function onMessage($owner, Telegram $telegram)
    {
        return false;
    }

    public function getName()
    {
        return join('', array_slice(explode('\\', get_class($this)), -1));
    }
}
