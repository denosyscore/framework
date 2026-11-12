<?php

declare(strict_types=1);

namespace Denosys\Support\Tests;

use Denosys\Support\Collection;
use PHPUnit\Framework\TestCase;

final class CollectionTest extends TestCase
{
    public function testCollectionProvidesModelQueryAndProjectionOperations(): void
    {
        $collection = new Collection([
            ['id' => 1, 'name' => 'first'],
            ['id' => 2, 'name' => 'second'],
        ]);

        self::assertFalse($collection->isEmpty());
        self::assertSame(2, $collection->count());
        self::assertSame(['id' => 1, 'name' => 'first'], $collection->first());
        self::assertSame([1, 2], $collection->pluck('id')->all());
        self::assertSame(['FIRST', 'SECOND'], $collection->pluck('name')->map('strtoupper')->all());
        self::assertSame([['id' => 2, 'name' => 'second']], $collection->filter(
            static fn (array $item): bool => $item['id'] === 2,
        )->values()->all());
        self::assertSame('[{"id":1,"name":"first"},{"id":2,"name":"second"}]', json_encode($collection));
        self::assertSame([], (new Collection())->all());
    }
}
