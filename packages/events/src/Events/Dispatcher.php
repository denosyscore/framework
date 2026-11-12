<?php

declare(strict_types=1);

namespace Denosys\Events;

use Psr\EventDispatcher\StoppableEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

readonly class Dispatcher implements EventDispatcherInterface
{
    public function __construct(
        private ListenerProvider $provider
    ) {
    }

    public function dispatch(object $event): object
    {
        $isStoppable = $event instanceof StoppableEventInterface;

        if ($isStoppable && $event->isPropagationStopped()) {
            return $event;
        }

        foreach ($this->provider->getListenersForEvent($event) as $listener) {
            $listener($event);

            if ($isStoppable && $event->isPropagationStopped()) {
                break;
            }
        }

        return $event;
    }

    public function listen(string $event, callable $listener): void
    {
        $this->provider->addListener($event, $listener);
    }
}
