<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Denosys\Database\Connection\Connection;
use InvalidArgumentException;

/**
 * Exists in database validation rule.
 *
 * Validates that a value exists in a specified database table.
 */
class Exists implements RuleInterface
{
    private ?Connection $connection = null;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!isset($parameters['table'])) {
            throw new InvalidArgumentException('Exists rule requires a table parameter');
        }

        if ($this->connection === null) {
            throw new InvalidArgumentException('Exists rule requires a database connection');
        }

        $table = $parameters['table'];
        $column = $parameters['column'] ?? $field;
        if (!is_string($column) || $column === '') {
            throw new InvalidArgumentException('Exists rule column must be a column name');
        }

        return $this->connection->table($table)
            ->where($column, '=', $value)
            ->exists();
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The selected :attribute is invalid.';
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['table', 'column'];
    }

    public static function ruleName(): string
    {
        return 'exists';
    }
}
