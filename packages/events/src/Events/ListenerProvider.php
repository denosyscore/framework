<?php

declare(strict_types=1);

namespace Denosys\Events;

use Psr\EventDispatcher\ListenerProviderInterface;

class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, array<callable>> */
    private array $listeners = [];

    public function addListener(string $eventClass, callable $listener): void
    {
        $this->listeners[$eventClass][] = $listener;
    }

    public function removeListener(string $eventClass, callable $listener): void
    {
        if (empty($this->listeners[$eventClass])) {
            return;
        }

        $key = array_search($listener, $this->listeners[$eventClass], true);
        if (false !== $key) {
            array_splice($this->listeners[$eventClass], $key, 1);
        }
    }

    public function clearListeners(?string $eventClass = null): void
    {
        if (null === $eventClass) {
            $this->listeners = [];
        } else {
            unset($this->listeners[$eventClass]);
        }
    }

    /**
     * @return iterable<callable>
     */
    public function getListenersForEvent(object $event): iterable
    {
        $classes = array_merge(
            [get_class($event)],
            class_parents($event) ?: [],
            class_implements($event) ?: []
        );

        foreach ($classes as $class) {
            if (!empty($this->listeners[$class])) {
                foreach ($this->listeners[$class] as $listener) {
                    yield $listener;
                }
            }
        }
    }
}
