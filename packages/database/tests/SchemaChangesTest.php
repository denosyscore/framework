<?php

declare(strict_types=1);

use Denosys\Database\Connection\Connection;
use Denosys\Database\Connection\ConnectionFactory;
use Denosys\Database\Schema\Blueprint;
use Denosys\Database\Schema\Grammar\MySqlSchemaGrammar;
use Denosys\Database\Schema\Grammar\PostgresSchemaGrammar;
use Denosys\Database\Schema\Grammar\SqliteSchemaGrammar;
use Denosys\Database\Schema\SchemaBuilder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class SchemaChangesTest extends TestCase
{
    private const TABLES = ['change_children', 'change_parents', 'change_rows', 'change_failed'];

    /** @return iterable<string, array{string}> */
    public static function drivers(): iterable
    {
        yield 'sqlite' => ['sqlite'];
        yield 'mysql' => ['mysql'];
        yield 'pgsql' => ['pgsql'];
    }

    #[DataProvider('drivers')]
    public function testACreateWhoseIndexFailsLeavesNoTable(string $driver): void
    {
        [, $schema] = $this->connect($driver);
        if ($driver === 'mysql') {
            self::markTestSkipped('MySQL commits CREATE TABLE implicitly; a later failure cannot remove it.');
        }

        $failed = false;
        try {
            $schema->create('change_failed', static function (Blueprint $table): void {
                $table->id();
                $table->string('a');
                $table->string('b');
                $table->index('a', 'change_failed_same_name');
                $table->index('b', 'change_failed_same_name'); // fails: the name is taken
            });
        } catch (\Denosys\Database\Exceptions\DatabaseException) {
            $failed = true;
        }

        self::assertTrue($failed, 'the second index with the same name was accepted');
        self::assertFalse($schema->hasTable('change_failed'));
    }

    #[DataProvider('drivers')]
    public function testDroppingAnIndexAndItsColumnTogether(string $driver): void
    {
        [$connection, $schema] = $this->connect($driver);
        $schema->create('change_rows', static function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->string('name');
            $table->index('email');
        });

        $schema->table('change_rows', static function (Blueprint $table): void {
            $table->dropIndex(['email']);
            $table->dropColumn('email');
        });

        self::assertFalse($schema->hasColumn('change_rows', 'email'));
        self::assertTrue($schema->hasColumn('change_rows', 'name'));
    }

    #[DataProvider('drivers')]
    public function testChangingAColumnKeepsItsData(string $driver): void
    {
        [$connection, $schema] = $this->connect($driver);
        $schema->create('change_rows', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 20);
            $table->integer('visits');
            $table->unique('name');
        });
        $connection->table('change_rows')->insert(['name' => 'ada', 'visits' => 3]);

        $schema->table('change_rows', static function (Blueprint $table): void {
            $table->string('name', 200)->nullable()->change();
            $table->bigInteger('visits')->default(0)->change();
        });

        $row = $connection->table('change_rows')->first();
        self::assertNotNull($row);
        self::assertSame('ada', $row->name);
        self::assertSame(3, (int) $row->visits);
        $connection->table('change_rows')->insert(['name' => null, 'visits' => 1]);
        $connection->table('change_rows')->insert(['name' => 'grace']); // visits now has a default
        self::assertSame(0, (int) $connection->table('change_rows')->where('name', '=', 'grace')->value('visits'));

        try {
            $connection->table('change_rows')->insert(['name' => 'ada', 'visits' => 9]);
            self::fail('the unique index was lost');
        } catch (Throwable) {
        }
    }

    #[DataProvider('drivers')]
    public function testAForeignKeyAddedToAnExistingTableIsEnforced(string $driver): void
    {
        [$connection, $schema] = $this->connect($driver);
        if ($driver === 'sqlite') {
            $connection->statement('PRAGMA foreign_keys = ON');
        }
        $schema->create('change_parents', static function (Blueprint $table): void {
            $table->id();
        });
        $schema->create('change_children', static function (Blueprint $table): void {
            $table->id();
            $table->string('label');
        });
        $connection->table('change_parents')->insert(['id' => 1]);
        $connection->table('change_children')->insert(['label' => 'kept']);

        $schema->table('change_children', static function (Blueprint $table): void {
            $table->foreignId('parent_id')->nullable();
            $table->foreign('parent_id')->references('id')->on('change_parents');
        });

        $connection->table('change_children')->insert(['label' => 'ok', 'parent_id' => 1]);
        self::assertSame(['kept', 'ok'], $connection->table('change_children')->orderBy('id')->pluck('label'));

        $this->expectException(Throwable::class);
        $connection->table('change_children')->insert(['label' => 'orphan', 'parent_id' => 99]);
    }

    #[DataProvider('drivers')]
    public function testColumnTypesAreReported(string $driver): void
    {
        [, $schema] = $this->connect($driver);
        $schema->create('change_rows', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });

        self::assertNotNull($schema->getColumnType('change_rows', 'name'));
        self::assertNull($schema->getColumnType('change_rows', 'missing'));
    }

    public function testRebuildingAParentTableNeverCascadesToItsChildren(): void
    {
        [$connection, $schema] = $this->connect('sqlite');
        $connection->statement('PRAGMA foreign_keys = ON');
        $schema->create('change_parents', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 10);
        });
        $schema->create('change_children', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id');
            $table->foreign('parent_id')->references('id')->on('change_parents')->cascadeOnDelete();
        });
        $connection->table('change_parents')->insert(['id' => 1, 'name' => 'p']);
        $connection->table('change_children')->insert(['parent_id' => 1]);

        $schema->table('change_parents', static function (Blueprint $table): void {
            $table->string('name', 100)->change();
        });

        self::assertSame(1, $connection->table('change_children')->count(), 'rebuilding the parent deleted its children');
        $this->expectException(Throwable::class);
        $connection->table('change_children')->insert(['parent_id' => 42]);
    }

    public function testAReferencedTableIsNotRebuiltInsideAnEnforcingTransaction(): void
    {
        [$connection, $schema] = $this->connect('sqlite');
        $connection->statement('PRAGMA foreign_keys = ON');
        $schema->create('change_parents', static function (Blueprint $table): void {
            $table->id();
            $table->string('name', 10);
        });
        $schema->create('change_children', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id');
            $table->foreign('parent_id')->references('id')->on('change_parents')->cascadeOnDelete();
        });
        $connection->table('change_parents')->insert(['id' => 1, 'name' => 'p']);
        $connection->table('change_children')->insert(['parent_id' => 1]);

        try {
            $connection->transaction(fn () => $schema->table('change_parents', static function (Blueprint $table): void {
                $table->string('name', 100)->change();
            }));
            self::fail('rebuilt a referenced table inside a transaction');
        } catch (LogicException $e) {
            self::assertStringContainsString('withinTransaction()', $e->getMessage());
        }

        self::assertSame(1, $connection->table('change_children')->count());
    }

    public function testDroppingAForeignKeyOnSqlite(): void
    {
        [$connection, $schema] = $this->connect('sqlite');
        $connection->statement('PRAGMA foreign_keys = ON');
        $schema->create('change_parents', static function (Blueprint $table): void {
            $table->id();
        });
        $schema->create('change_children', static function (Blueprint $table): void {
            $table->id();
            $table->foreignId('parent_id');
            $table->foreign('parent_id')->references('id')->on('change_parents');
            $table->index('parent_id');
        });

        $schema->table('change_children', static function (Blueprint $table): void {
            $table->dropForeign('fk_change_children_parent_id_change_parents_id');
        });

        $connection->table('change_children')->insert(['parent_id' => 42]);
        self::assertSame(1, $connection->table('change_children')->count());
        self::assertContains('idx_change_children_parent_id', array_column($schema->getIndexes('change_children'), 'name'));
    }

    public function testATableWithTriggersIsNotRebuilt(): void
    {
        [$connection, $schema] = $this->connect('sqlite');
        $schema->create('change_rows', static function (Blueprint $table): void {
            $table->id();
            $table->string('name');
        });
        $connection->statement('CREATE TRIGGER change_rows_touch AFTER UPDATE ON change_rows BEGIN SELECT 1; END');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('triggers');
        $schema->table('change_rows', static function (Blueprint $table): void {
            $table->string('name', 5)->change();
        });
    }

    public function testPostgresIntrospectsTheConnectionSchema(): void
    {
        [$admin] = $this->connect('pgsql');
        foreach (['change_tenant', 'Change Reports'] as $name) {
            $admin->statement('DROP SCHEMA IF EXISTS "' . $name . '" CASCADE');
            $admin->statement('CREATE SCHEMA "' . $name . '"');
        }
        $admin->statement('CREATE TABLE IF NOT EXISTS public.change_rows (id int, legacy int)');

        try {
            [$connection, $schema] = $this->connect('pgsql', ['schema' => ['change_tenant', 'public']]);
            $schema->create('change_rows', static function (Blueprint $table): void {
                $table->id();
                $table->string('email');
            });
            $schema->create('Change Reports.events', static function (Blueprint $table): void {
                $table->id();
            });

            self::assertSame('change_tenant', $connection->selectOne('SELECT current_schema() AS s')?->s);
            self::assertTrue($schema->hasColumn('change_rows', 'email'));
            self::assertFalse($schema->hasColumn('change_rows', 'legacy'));
            self::assertTrue($schema->hasTable('Change Reports.events'));
            self::assertFalse($schema->hasTable('events'));
            self::assertSame(['change_rows'], $schema->getTables());

            $schema->dropAllTables();

            self::assertSame([], $schema->getTables());
            self::assertTrue($schema->hasTable('public.change_rows'), 'dropAllTables() reached another schema');
            self::assertTrue($schema->hasTable('Change Reports.events'), 'dropAllTables() reached another schema');
        } finally {
            foreach (['change_tenant', 'Change Reports'] as $name) {
                $admin->statement('DROP SCHEMA IF EXISTS "' . $name . '" CASCADE');
            }
            $admin->statement('DROP TABLE IF EXISTS public.change_rows');
        }
    }

    /**
     * @param array<string, mixed> $extra
     * @return array{Connection, SchemaBuilder}
     */
    private function connect(string $driver, array $extra = []): array
    {
        $config = match ($driver) {
            'sqlite' => ['driver' => 'sqlite', 'database' => ':memory:'],
            'mysql' => $this->serverConfig('mysql', 'MYSQL', 3306, 'root'),
            'pgsql' => $this->serverConfig('pgsql', 'PGSQL', 5432, 'postgres'),
            default => throw new InvalidArgumentException($driver),
        };
        $connection = (new ConnectionFactory())->make([...$config, ...$extra]);
        $schema = new SchemaBuilder($connection, match ($driver) {
            'sqlite' => new SqliteSchemaGrammar(),
            'mysql' => new MySqlSchemaGrammar(),
            default => new PostgresSchemaGrammar(),
        });
        if ($extra === []) {
            foreach (self::TABLES as $table) {
                $schema->dropIfExists($table);
            }
        }

        return [$connection, $schema];
    }

    /** @return array<string, mixed> */
    private function serverConfig(string $driver, string $prefix, int $port, string $user): array
    {
        $database = getenv("DENOSYS_TEST_{$prefix}_DATABASE");
        if (!$database) {
            self::markTestSkipped("Set DENOSYS_TEST_{$prefix}_DATABASE to run against a real {$driver} server.");
        }

        return [
            'driver' => $driver,
            'host' => getenv("DENOSYS_TEST_{$prefix}_HOST") ?: '127.0.0.1',
            'port' => (int) (getenv("DENOSYS_TEST_{$prefix}_PORT") ?: $port),
            'database' => $database,
            'username' => getenv("DENOSYS_TEST_{$prefix}_USERNAME") ?: $user,
            'password' => getenv("DENOSYS_TEST_{$prefix}_PASSWORD") ?: '',
        ];
    }
}
