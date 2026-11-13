<?php

declare(strict_types=1);

use Denosys\Database\Connection\Connection;
use Denosys\Database\Connection\ConnectionManager;
use Denosys\Database\Exceptions\InvalidRecord;
use Denosys\Database\Exceptions\RecordNotFound;
use Denosys\Database\Record\RecordStore;
use PHPUnit\Framework\TestCase;
use RecordFixtures\Gadget;
use RecordFixtures\Widget;

require_once __DIR__ . '/fixtures.php';

final class RecordStoreTest extends TestCase
{
    private Connection $db;
    private RecordStore $store;

    protected function setUp(): void
    {
        $manager = new ConnectionManager();
        $manager->addConnection('default', ['driver' => 'sqlite', 'database' => ':memory:']);
        $manager->setDefaultConnection('default');
        $this->db = $manager->connection();
        $this->db->statement("CREATE TABLE widgets (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, notes TEXT NULL, status TEXT NOT NULL DEFAULT 'new', extra TEXT NULL, created_at TEXT NULL, updated_at TEXT NULL)");
        $this->db->statement('CREATE TABLE gadgets (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL)');
        $this->store = new RecordStore($manager);
    }

    private function widget(string $name): Widget
    {
        $widget = new Widget();
        $widget->name = $name;
        $widget->status = 'new';
        $this->store->save($widget);

        return $widget;
    }

    /** @return list<string> */
    private function queries(callable $run): array
    {
        $this->db->flushQueryLog();
        $this->db->enableQueryLog();
        $run();
        $this->db->disableQueryLog();

        return array_column($this->db->getQueryLog(), 'query');
    }

    public function testColumnsWithoutPropertiesAreIgnored(): void
    {
        $this->db->statement("INSERT INTO widgets (name, extra) VALUES ('a', 'x')");

        $widget = $this->store->findOrFail(Widget::class, 1);

        self::assertSame('a', $widget->name);
        self::assertSame('new', $widget->status);
        self::assertSame(1, $widget->id);
        self::assertTrue($this->store->isPersisted($widget));
    }

    public function testInsertAssignsTheGeneratedId(): void
    {
        $gadget = new Gadget();
        $gadget->name = 'g';

        self::assertFalse($this->store->isPersisted($gadget));
        $this->store->save($gadget);

        self::assertSame(1, $gadget->id);
        self::assertTrue($this->store->isPersisted($gadget));
    }

    public function testInsertSkipsUninitializedPropertiesAndRefreshes(): void
    {
        $widget = new Widget();
        $widget->name = 'a';

        $this->store->save($widget);

        self::assertSame('new', $widget->status, 'the column default is read back');
    }

    public function testUpdateWritesOnlyChangedColumns(): void
    {
        $gadget = new Gadget();
        $gadget->name = 'a';
        $this->store->save($gadget);
        $gadget->name = 'b';

        $queries = $this->queries(fn () => $this->store->save($gadget));

        self::assertSame(['update "gadgets" set "name" = ? where "id" = ?'], array_map('strtolower', $queries));
        self::assertSame('b', $this->store->findOrFail(Gadget::class, 1)->name);
    }

    public function testSavingAnUnchangedRecordRunsNoQuery(): void
    {
        $widget = $this->widget('a');
        self::assertNull($widget->notes);
        $loaded = $this->store->findOrFail(Widget::class, (int) $widget->id);

        self::assertSame([], $this->queries(fn () => $this->store->save($loaded)));
    }

    public function testCreatedAtAndUpdatedAtAreSetWhenDeclared(): void
    {
        $widget = $this->widget('a');

        self::assertInstanceOf(DateTimeImmutable::class, $widget->createdAt);
        self::assertInstanceOf(DateTimeImmutable::class, $widget->updatedAt);

        $widget->updatedAt = new DateTimeImmutable('2000-01-01 00:00:00');
        $this->store->save($widget);
        $widget->name = 'b';
        $this->store->save($widget);

        self::assertGreaterThan(new DateTimeImmutable('2001-01-01'), $widget->updatedAt);
        $row = $this->db->table('widgets')->first();
        self::assertIsObject($row);
        self::assertSame($widget->createdAt->format('Y-m-d H:i:s'), $row->created_at);
    }

    public function testDeleteRemovesTheRowAndMarksTheRecordNew(): void
    {
        $widget = $this->widget('a');

        $this->store->delete($widget);

        self::assertFalse($this->store->isPersisted($widget));
        self::assertSame(0, $this->db->table('widgets')->count());
    }

    public function testDeletingANewRecordIsRefused(): void
    {
        $this->expectException(InvalidRecord::class);

        $this->store->delete(new Gadget());
    }

    public function testFindOrFailThrowsRecordNotFound(): void
    {
        try {
            $this->store->findOrFail(Gadget::class, 9);
            self::fail('No exception');
        } catch (RecordNotFound $e) {
            self::assertSame(Gadget::class, $e->record);
            self::assertSame(9, $e->id);
        }

        self::assertNull($this->store->find(Gadget::class, 9));
    }

    public function testRefreshReloadsTheRow(): void
    {
        $widget = $this->widget('a');
        $this->db->table('widgets')->update(['name' => 'changed']);
        $widget->notes = 'local';

        $this->store->refresh($widget);

        self::assertSame('changed', $widget->name);
        self::assertNull($widget->notes);
        self::assertSame([], $this->queries(fn () => $this->store->save($widget)));
    }

    public function testStateIsReleasedWithTheRecord(): void
    {
        $widget = $this->widget('a');
        self::assertSame(1, $this->store->stateCount());

        unset($widget);

        self::assertSame(0, $this->store->stateCount());
    }
}
