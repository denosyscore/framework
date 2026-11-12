<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Framework\Console\CommandRegistry;
use Denosys\Queue\Console\QueueWorkCommand;
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
        $commands = $container->has(CommandRegistry::class) ? $container->get(CommandRegistry::class) : null;
        if ($commands instanceof CommandRegistry) {
            $commands->add(QueueWorkCommand::class);
        }

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

