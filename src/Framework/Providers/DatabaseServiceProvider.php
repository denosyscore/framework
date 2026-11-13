<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Database\Record\Records;
use Denosys\Database\Record\RecordStore;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Denosys\Config\Repository;
use Denosys\Database\Connection\Connection;
use Psr\EventDispatcher\EventDispatcherInterface;
use Denosys\Database\Connection\ConnectionFactory;
use Denosys\Database\Connection\ConnectionManager;

class DatabaseServiceProvider implements Provider
{
    /**
     * Register database services.
     */
    public function register(ContainerInterface $container): void
    {
        // Register the connection factory
        $container->singleton(ConnectionFactory::class, function () {
            return new ConnectionFactory();
        });

        // Register the connection manager
        $container->singleton(ConnectionManager::class, function (ContainerInterface $container) {
            $factory = $container->get(ConnectionFactory::class);
            $manager = new ConnectionManager($factory);

            // Load database configuration
            $config = $container->get(Repository::class);
            $databaseConfig = $config->get('database', []);

            // Set default connection
            $defaultConnection = $databaseConfig['default'] ?? 'mysql';
            $manager->setDefaultConnection($defaultConnection);

            // Register all configured connections
            $connections = $databaseConfig['connections'] ?? [];
            foreach ($connections as $name => $connectionConfig) {
                $manager->addConnection($name, $connectionConfig);
            }

            return $manager;
        });

        // Register a convenient 'db' alias for the default connection
        $container->singleton('db', function (ContainerInterface $container) {
            return $container->get(ConnectionManager::class)->connection();
        });

        // Register Connection interface binding
        $container->singleton(Connection::class, function (ContainerInterface $container) {
            return $container->get(ConnectionManager::class)->connection();
        });
    }

    /**
     * Boot database services.
     */
    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        // Records (Record + Persists) load and save through this store.
        $connections = $container->get(ConnectionManager::class);
        if (!$connections instanceof ConnectionManager) {
            throw new \LogicException('The container must provide a ConnectionManager.');
        }

        Records::use(new RecordStore($connections));
    }
}
