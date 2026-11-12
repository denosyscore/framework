<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Container\Container;
use Denosys\Database\Connection\Connection;
use Denosys\Database\Connection\ConnectionFactory;
use Denosys\Validation\ValidationServiceProvider;
use Denosys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class DatabaseRuleBindingTest extends TestCase
{
    protected function tearDown(): void
    {
        Validator::reset();
    }

    public function testUniqueAndExistsUseTypedConnectionBinding(): void
    {
        $connection = (new ConnectionFactory())->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $connection->statement('CREATE TABLE accounts (email TEXT NOT NULL)');
        $connection->statement("INSERT INTO accounts (email) VALUES ('taken@example.test')");

        $container = new Container();
        $container->instance(Connection::class, $connection);
        self::assertFalse($container->has('db'));

        (new ValidationServiceProvider())->boot($container);

        $unique = Validator::make(
            ['email' => 'taken@example.test'],
            ['email' => 'unique:accounts,email'],
        );
        self::assertFalse($unique->validate());
        self::assertTrue($unique->errors()->has('email'));

        $exists = Validator::make(
            ['email' => 'taken@example.test'],
            ['email' => 'exists:accounts,email'],
        );
        self::assertTrue($exists->validate());
    }

    public function testLegacyDbBindingRemainsSupported(): void
    {
        $connection = (new ConnectionFactory())->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $connection->statement('CREATE TABLE accounts (email TEXT NOT NULL)');
        $connection->statement("INSERT INTO accounts (email) VALUES ('taken@example.test')");

        $container = new Container();
        $container->instance('db', $connection);
        (new ValidationServiceProvider())->boot($container);

        self::assertTrue(Validator::make(
            ['email' => 'taken@example.test'],
            ['email' => 'exists:accounts,email'],
        )->validate());
    }

    public function testTypedConnectionTakesPrecedenceOverLegacyDbBinding(): void
    {
        $typed = (new ConnectionFactory())->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $typed->statement('CREATE TABLE accounts (email TEXT NOT NULL)');
        $typed->statement("INSERT INTO accounts (email) VALUES ('typed@example.test')");

        $legacy = (new ConnectionFactory())->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $legacy->statement('CREATE TABLE accounts (email TEXT NOT NULL)');

        $container = new Container();
        $container->instance(Connection::class, $typed);
        $container->instance('db', $legacy);
        (new ValidationServiceProvider())->boot($container);

        self::assertTrue(Validator::make(
            ['email' => 'typed@example.test'],
            ['email' => 'exists:accounts,email'],
        )->validate());
    }
}
