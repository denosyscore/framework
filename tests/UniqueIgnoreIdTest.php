<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Database\Connection\ConnectionFactory;
use Denosys\Validation\Rules\Unique;
use PHPUnit\Framework\TestCase;

final class UniqueIgnoreIdTest extends TestCase
{
    public function testDeclaredIgnoreIdExcludesOnlyTheCurrentRow(): void
    {
        $connection = (new ConnectionFactory())->make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);
        $connection->statement('CREATE TABLE accounts (id INTEGER PRIMARY KEY, email TEXT NOT NULL)');
        $connection->statement("INSERT INTO accounts (id, email) VALUES (1, 'taken@example.test')");
        $connection->statement("INSERT INTO accounts (id, email) VALUES (2, 'other@example.test')");

        $rule = new Unique();
        $rule->setConnection($connection);

        self::assertContains('ignore_id', Unique::parameterNames());
        self::assertTrue($rule->validate(
            'email',
            'taken@example.test',
            ['table' => 'accounts', 'column' => 'email', 'ignore_id' => 1],
        ));
        self::assertFalse($rule->validate(
            'email',
            'taken@example.test',
            ['table' => 'accounts', 'column' => 'email', 'ignore_id' => 2],
        ));
    }
}
