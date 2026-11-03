<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Denosys\Database\Connection\Connection;
use InvalidArgumentException;

/**
 * Unique database validation rule.
 *
 * Validates that a value does not already exist in a specified database table.
 */
class Unique implements RuleInterface
{
    private ?Connection $connection = null;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @param array<int, string> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!array_key_exists('table', $parameters)) {
            throw new InvalidArgumentException('Unique rule requires a table parameter');
        }

        if ($this->connection === null) {
            throw new InvalidArgumentException('Unique rule requires a database connection');
        }

        $table = $parameters['table'];
        $column = $parameters['column'] ?? $field;
        $ignoreId = $parameters['ignore_id'] ?? null;

        $query = $this->connection->table($table)
            ->where($column, '=', $value);

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->doesntExist();
    }

    /**
     * @param array<int, string> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute has already been taken.';
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['table', 'column', 'ignore_id'];
    }

    public static function ruleName(): string
    {
        return 'unique';
    }
}
