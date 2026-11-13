<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Framework\Console\CommandRegistry;
use Denosys\Database\Console\DbSeedCommand;
use Denosys\Database\Console\MigrateFreshCommand;
use Denosys\Database\Console\MigrateStatusCommand;
use Denosys\Database\Console\MigrateRollbackCommand;
use Denosys\Database\Console\MigrateCommand;
use Denosys\Database\Migration\MigrationRepository;
use Denosys\Database\Migration\Migrator;
use Denosys\Container\ContainerInterface;
use Denosys\Database\Connection\Connection;
use Denosys\Database\Schema\SchemaBuilder;
use Denosys\Database\Schema\Grammar\MySqlSchemaGrammar;
use Denosys\Database\Schema\Grammar\PostgresSchemaGrammar;
use Denosys\Database\Schema\Grammar\SqliteSchemaGrammar;
use Denosys\Database\Schema\Grammar\SchemaGrammarInterface;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Service provider for migration system.
 */
class MigrationServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        $commands = $container->has(CommandRegistry::class) ? $container->get(CommandRegistry::class) : null;
        if ($commands instanceof CommandRegistry) {
            $commands->add(MigrateCommand::class, MigrateRollbackCommand::class, MigrateStatusCommand::class, MigrateFreshCommand::class, DbSeedCommand::class);
        }

        // Register SchemaGrammar based on database driver
        $container->singleton(SchemaGrammarInterface::class, function (ContainerInterface $c) {
            $connection = $c->get(Connection::class);
            if (!$connection instanceof Connection) {
                throw new \LogicException('The container did not return a database connection.');
            }
            $driver = $connection->getDriverName();

            return match ($driver) {
                'mysql' => new MySqlSchemaGrammar(),
                'pgsql', 'postgres' => new PostgresSchemaGrammar(),
                'sqlite' => new SqliteSchemaGrammar(),
                default => throw new \LogicException("No schema grammar for the [{$driver}] driver."),
            };
        });

        // Register SchemaBuilder
        $container->singleton(SchemaBuilder::class, function (ContainerInterface $c) {
            return new SchemaBuilder(
                $c->get(Connection::class),
                $c->get(SchemaGrammarInterface::class),
            );
        });

        // Register MigrationRepository
        $container->singleton(MigrationRepository::class, function (ContainerInterface $c) {
            return new MigrationRepository(
                $c->get(Connection::class),
                $c->get(SchemaBuilder::class),
            );
        });

        // Register Migrator
        $container->singleton(Migrator::class, function (ContainerInterface $c) {
            $basePath = $c->has('path.base') 
                ? $c->get('path.base') 
                : getcwd();
                
            return new Migrator(
                $c->get(Connection::class),
                $c->get(SchemaBuilder::class),
                $c->get(MigrationRepository::class),
                $basePath . '/database/migrations',
            );
        });
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        // Nothing to boot
    }
}
