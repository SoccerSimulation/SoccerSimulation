<?php

namespace SoccerSimulation\Common\Event;

use Symfony\Component\EventDispatcher\Event;

trait EventGenerator
{
    private $pendingEvents = [];

    /**
     * @param Event $event
     */
    protected function raise(Event $event)
    {
        $this->pendingEvents[] = $event;
    }

    /**
     * @param Event[] $events
     */
    protected function raiseMultiple(array $events)
    {
        $this->pendingEvents = array_merge($this->pendingEvents, $events);
    }

    /**
     * @return Event[]
     */
    public function releaseEvents()
    {
        $events = $this->pendingEvents;
        $this->pendingEvents = [];

        return $events;
    }
}
