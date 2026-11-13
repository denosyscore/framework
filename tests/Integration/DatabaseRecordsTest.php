<?php

declare(strict_types=1);

namespace Tests\Integration;

use Denosys\Container\Container;
use Denosys\Database\Connection\ConnectionManager;
use Denosys\Database\Record\Records;
use Denosys\Framework\Providers\DatabaseServiceProvider;
use PHPUnit\Framework\TestCase;

/** Booting the database provider gives records their store. */
final class DatabaseRecordsTest extends TestCase
{
    protected function tearDown(): void
    {
        Records::reset();
    }

    public function testBootSetsTheRecordStore(): void
    {
        $container = new Container();
        $manager = new ConnectionManager();
        $manager->addConnection('default', ['driver' => 'sqlite', 'database' => ':memory:']);
        $manager->setDefaultConnection('default');
        $container->instance(ConnectionManager::class, $manager);
        Records::reset();

        (new DatabaseServiceProvider())->boot($container);

        self::assertInstanceOf(\Denosys\Database\Record\RecordStore::class, Records::store());
    }
}
