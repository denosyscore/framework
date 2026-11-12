<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Events\Dispatcher;
use Denosys\Events\ListenerProvider;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Denosys\Container\Exceptions\NotFoundException;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventServiceProvider implements Provider
{
    /**
     * @throws NotFoundException
     */
    public function register(ContainerInterface $container): void
    {
        // Only register if not already registered by Kernel
        // This supports both HTTP (Kernel pre-registers) and CLI/testing scenarios
        if (!$container->has(Dispatcher::class)) {
            $container->singleton(Dispatcher::class, fn() => new Dispatcher(new ListenerProvider()));
        }

        // Only register interface binding if not already present
        if (!$container->has(EventDispatcherInterface::class)) {
            $container->singleton(
                EventDispatcherInterface::class,
                fn(ContainerInterface $container) => $container->get(Dispatcher::class)
            );
        }

        // Alias is safe to set regardless (will just overwrite)
        $container->alias('events', EventDispatcherInterface::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        // The package registers no listeners of its own.
    }
}
