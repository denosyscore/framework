<?php

declare(strict_types=1);

namespace Denosys\Database\Record\Relations;

use Closure;
use Denosys\Database\Record\Record;
use Denosys\Database\Record\RecordQuery;
use Denosys\Database\Record\RecordStore;

/**
 * Key matching shared by the relation classes.
 *
 * @internal
 */
final class Matching
{
    /**
     * The distinct, non-null values of one column across records.
     *
     * @param list<Record> $records
     * @return list<int|string>
     */
    public static function values(RecordStore $store, array $records, string $column): array
    {
        $values = [];
        foreach ($records as $record) {
            $value = $store->columnValue($record, $column);
            if (is_int($value) || is_string($value)) {
                $values[self::key($value)] = $value;
            }
        }

        return array_values($values);
    }

    /** A dictionary key for a column value, so 1 and '1' match. */
    public static function key(mixed $value): string
    {
        return is_int($value) || is_string($value) ? (string) $value : '';
    }

    /**
     * @template R of Record
     * @param RecordQuery<R> $query
     * @param (Closure(RecordQuery<R>): RecordQuery<R>)|null $constraint
     * @param list<Closure> $nested
     * @return RecordQuery<R>
     */
    public static function apply(RecordQuery $query, ?Closure $constraint, array $nested): RecordQuery
    {
        $query = $constraint === null ? $query : $constraint($query);

        return $nested === [] ? $query : $query->with(...$nested);
    }

    /**
     * @template R of Record
     * @param (Closure(RecordQuery<R>): RecordQuery<R>)|null $first
     * @param Closure(RecordQuery<R>): RecordQuery<R> $then
     * @return Closure(RecordQuery<R>): RecordQuery<R>
     */
    public static function compose(?Closure $first, Closure $then): Closure
    {
        return $first === null ? $then : static fn (RecordQuery $query): RecordQuery => $then($first($query));
    }
}
