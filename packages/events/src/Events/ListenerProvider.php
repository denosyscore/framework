<?php

declare(strict_types=1);

namespace Denosys\Events;

use Closure;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

/**
 * PSR-14 listener provider.
 *
 * A listener is a callable, or the name of an invokable class. A class
 * listener is resolved from the PSR-11 container only when its event is
 * dispatched, so registering listeners never builds them or their
 * dependencies. Listeners registered for an event's parent class or for an
 * interface it implements receive it too.
 */
final class ListenerProvider implements ListenerProviderInterface
{
    /** @var array<string, list<callable|class-string>> */
    private array $listeners = [];

    public function __construct(private readonly ?ContainerInterface $container = null)
    {
    }

    /**
     * Listen for an event with a callable or an invokable class name.
     *
     * @param callable|class-string $listener
     */
    public function on(string $eventClass, callable|string $listener): void
    {
        if (is_string($listener) && !is_callable($listener)) {
            if ($this->container === null) {
                throw new \LogicException("Listener [{$listener}] is a class name, which needs a container to resolve it.");
            }
            if (!class_exists($listener) || !method_exists($listener, '__invoke')) {
                throw new \InvalidArgumentException("Listener [{$listener}] must be an invokable class.");
            }
        }

        $this->listeners[$eventClass][] = $listener;
    }

    public function addListener(string $eventClass, callable $listener): void
    {
        $this->on($eventClass, $listener);
    }

    public function removeListener(string $eventClass, callable|string $listener): void
    {
        $key = array_search($listener, $this->listeners[$eventClass] ?? [], true);
        if ($key !== false) {
            array_splice($this->listeners[$eventClass], $key, 1);
        }
    }

    public function clearListeners(?string $eventClass = null): void
    {
        if ($eventClass === null) {
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
        $classes = [$event::class, ...array_values(class_parents($event) ?: []), ...array_values(class_implements($event) ?: [])];

        foreach ($classes as $class) {
            foreach ($this->listeners[$class] ?? [] as $listener) {
                yield is_callable($listener) ? $listener : $this->resolve($listener);
            }
        }
    }

    /**
     * @param class-string $class
     */
    private function resolve(string $class): Closure
    {
        $container = $this->container;

        return static function (object $event) use ($container, $class): void {
            $listener = $container?->get($class);
            if (!is_callable($listener)) {
                throw new \LogicException("The container did not return an invokable [{$class}] listener.");
            }

            $listener($event);
        };
    }
}
