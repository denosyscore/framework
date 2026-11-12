<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Queue\QueueManager;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Service provider for the queue system.
 */
class QueueServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(QueueManager::class, function (ContainerInterface $c) {
            // QueueManager will load its own config if not provided
            return new QueueManager($c);
        });
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        // Nothing to boot
    }
}

