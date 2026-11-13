<?php

declare(strict_types=1);

namespace Denosys\Database\Record;

use Closure;
use Denosys\Database\Exceptions\RecordNotFound;
use Denosys\Database\Query\Builder;
use Denosys\Database\Query\Expression;
use Generator;

/**
 * A query for records of one class. It wraps the immutable query builder:
 * every modifier returns a new query and leaves this one unchanged.
 *
 * Use it to load records one entity at a time. For reports, sums and other
 * set-based work, use the query builder (toBase()) and its rows.
 *
 * @template T of object
 */
final readonly class RecordQuery
{
    /** @param class-string<T> $class */
    public function __construct(
        private RecordStore $store,
        private string $class,
        private Builder $builder,
    ) {
    }

    /**
     * @param string|(Closure(Builder): mixed) $column
     * @return self<T>
     */
    #[\NoDiscard]
    public function where(string|Closure $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->with($this->builder->where($column, $operator, $value));
    }

    /**
     * @param string|(Closure(Builder): mixed) $column
     * @return self<T>
     */
    #[\NoDiscard]
    public function orWhere(string|Closure $column, mixed $operator = null, mixed $value = null): self
    {
        return $this->with($this->builder->orWhere($column, $operator, $value));
    }

    /**
     * @param array<int|string, mixed>|Builder $values
     * @return self<T>
     */
    #[\NoDiscard]
    public function whereIn(string $column, array|Builder $values): self
    {
        return $this->with($this->builder->whereIn($column, $values));
    }

    /**
     * @param array<int|string, mixed>|Builder $values
     * @return self<T>
     */
    #[\NoDiscard]
    public function whereNotIn(string $column, array|Builder $values): self
    {
        return $this->with($this->builder->whereNotIn($column, $values));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function whereNull(string $column): self
    {
        return $this->with($this->builder->whereNull($column));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function whereNotNull(string $column): self
    {
        return $this->with($this->builder->whereNotNull($column));
    }

    /**
     * @param array<int|string, mixed> $values Exactly two bounds.
     * @return self<T>
     */
    #[\NoDiscard]
    public function whereBetween(string $column, array $values): self
    {
        return $this->with($this->builder->whereBetween($column, $values));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function whereColumn(string $first, string $operator, string $second): self
    {
        return $this->with($this->builder->whereColumn($first, $operator, $second));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function whereExists(Builder $query): self
    {
        return $this->with($this->builder->whereExists($query));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function whereNotExists(Builder $query): self
    {
        return $this->with($this->builder->whereNotExists($query));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function orderBy(string|Expression $column, string $direction = 'asc'): self
    {
        return $this->with($this->builder->orderBy($column, $direction));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function orderByDesc(string|Expression $column): self
    {
        return $this->with($this->builder->orderByDesc($column));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function latest(string $column = 'created_at'): self
    {
        return $this->with($this->builder->latest($column));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function oldest(string $column = 'created_at'): self
    {
        return $this->with($this->builder->oldest($column));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function limit(int $limit): self
    {
        return $this->with($this->builder->limit($limit));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function offset(int $offset): self
    {
        return $this->with($this->builder->offset($offset));
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function lockForUpdate(): self
    {
        return $this->with($this->builder->lockForUpdate());
    }

    /** @return self<T> */
    #[\NoDiscard]
    public function sharedLock(): self
    {
        return $this->with($this->builder->sharedLock());
    }

    /**
     * Applies any query builder constraint the methods above do not cover.
     *
     * @param Closure(Builder): Builder $constraint
     * @return self<T>
     */
    #[\NoDiscard]
    public function tap(Closure $constraint): self
    {
        return $this->with($constraint($this->builder));
    }

    /** @return list<T> */
    public function get(): array
    {
        return $this->hydrate($this->rows($this->builder));
    }

    /** @return T|null */
    public function first(): ?object
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * @return T
     *
     * @throws RecordNotFound
     */
    public function firstOrFail(): object
    {
        return $this->first() ?? throw new RecordNotFound($this->class, '(first match)');
    }

    /** @return T|null */
    public function find(int|string $id): ?object
    {
        return $this->where($this->qualifiedKey(), '=', $id)->first();
    }

    /**
     * @return T
     *
     * @throws RecordNotFound
     */
    public function findOrFail(int|string $id): object
    {
        return $this->find($id) ?? throw new RecordNotFound($this->class, $id);
    }

    public function count(): int
    {
        return $this->builder->count();
    }

    public function exists(): bool
    {
        return $this->builder->exists();
    }

    /**
     * Walks every matching record in key order, $chunkSize per query.
     *
     * @return Generator<int, T>
     */
    public function lazyById(int $chunkSize = 1000): Generator
    {
        $key = RecordMetadata::of($this->class)->id->column;
        foreach ($this->builder->lazyById($chunkSize, $key) as $row) {
            yield $this->store->hydrate($this->class, self::row($row));
        }
    }

    /**
     * Updates every matching row. Loaded records are not changed.
     *
     * @param array<string, mixed> $values column => value
     */
    public function update(array $values): int
    {
        return $this->builder->update($values);
    }

    /** Deletes every matching row. Loaded records are not changed. */
    public function delete(): int
    {
        return $this->builder->delete();
    }

    /** The underlying query builder, for set-based work. */
    public function toBase(): Builder
    {
        return $this->builder;
    }

    /**
     * @return self<T>
     */
    private function with(Builder $builder): self
    {
        return new self($this->store, $this->class, $builder);
    }

    private function qualifiedKey(): string
    {
        $meta = RecordMetadata::of($this->class);

        return $meta->table . '.' . $meta->id->column;
    }

    /** @return list<object> */
    private function rows(Builder $builder): array
    {
        $result = $builder->get();
        $rows = [];
        foreach (is_array($result) ? $result : [] as $row) {
            if (is_object($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * @param list<object> $rows
     * @return list<T>
     */
    private function hydrate(array $rows): array
    {
        return array_map(fn (object $row): object => $this->store->hydrate($this->class, self::row($row)), $rows);
    }

    /** @return array<string, mixed> */
    private static function row(object $row): array
    {
        $values = [];
        foreach (get_object_vars($row) as $column => $value) {
            if (is_string($column)) {
                $values[$column] = $value;
            }
        }

        return $values;
    }
}
