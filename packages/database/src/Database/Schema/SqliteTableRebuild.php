<?php

declare(strict_types=1);

namespace Denosys\Database\Schema;

use Denosys\Database\Connection\Connection;
use Denosys\Database\Schema\Grammar\SqliteSchemaGrammar;
use LogicException;

/**
 * Applies an ALTER that SQLite cannot express (changing a column, adding or
 * dropping a foreign key) by rebuilding the table, following SQLite's
 * documented procedure (https://www.sqlite.org/lang_altertable.html#otheralter)
 * in one transaction: create the new table under a temporary name, copy the
 * rows, drop the old table, rename the new one into place, recreate the
 * indexes, then check every foreign key.
 *
 * Dropping a table that other tables reference runs an implicit DELETE, which
 * fires their ON DELETE actions, so enforcement must be off for the rebuild.
 * SQLite can only turn it off outside a transaction:
 *
 * - outside a transaction, enforcement is turned off for the rebuild and on
 *   again afterwards, and foreign_key_check must pass before it commits;
 * - inside a transaction (a migration, by default), a table that no other
 *   table references is rebuilt with enforcement on; a referenced one is
 *   refused, because it cannot be rebuilt safely there.
 *
 * A table with triggers, CHECK constraints or generated columns is refused,
 * because a rebuild would not keep them.
 *
 * @internal Used by SchemaBuilder.
 */
final class SqliteTableRebuild
{
    public function __construct(
        private readonly Connection $connection,
        private readonly SqliteSchemaGrammar $grammar,
    ) {
    }

    public function apply(Blueprint $blueprint): void
    {
        $table = $blueprint->getTable();
        $definition = $this->tableSql($table);
        if (preg_match('/\b(CHECK|GENERATED\s+ALWAYS)\b|\bAS\s*\(/i', $definition) === 1) {
            throw new LogicException("Cannot rebuild [{$table}]: it has CHECK constraints or generated columns, which a rebuild would lose.");
        }
        if ($this->rows("SELECT 1 FROM sqlite_master WHERE type = 'trigger' AND tbl_name = ?", [$table]) !== []) {
            throw new LogicException("Cannot rebuild [{$table}]: it has triggers, which a rebuild would lose.");
        }

        $renames = $blueprint->getRenameColumns();
        $dropped = $blueprint->getDropColumns();
        $changed = [];
        $added = [];
        foreach ($blueprint->getColumns() as $column) {
            if ($column->isChange()) {
                $changed[$column->getName()] = $column;
            } else {
                $added[] = $column;
            }
        }

        // Columns: existing ones in order (renamed, changed or kept), then new ones.
        $columns = [];
        $copy = [];
        $primary = [];
        $autoIncrement = stripos($definition, 'AUTOINCREMENT') !== false;
        foreach ($this->rows('PRAGMA table_info(' . $this->quote($table) . ')') as $row) {
            $old = self::text($row, 'name');
            if (in_array($old, $dropped, true)) {
                continue;
            }
            $new = $renames[$old] ?? $old;
            if (self::number($row, 'pk') > 0) {
                $primary[self::number($row, 'pk')] = $new;
            }
            $copy[$new] = $old;

            if (isset($changed[$new])) {
                $columns[$new] = $this->grammar->columnDefinition($changed[$new]);
                unset($changed[$new]);
                continue;
            }

            $sql = $this->quote($new) . ' ' . (self::text($row, 'type') ?: 'BLOB');
            if (self::number($row, 'notnull') === 1) {
                $sql .= ' NOT NULL';
            }
            if (($row['dflt_value'] ?? null) !== null) {
                $sql .= ' DEFAULT ' . self::text($row, 'dflt_value');
            }
            $columns[$new] = $sql;
        }
        if ($changed !== []) {
            throw new LogicException("Cannot change missing column(s) [" . implode(', ', array_keys($changed)) . "] on [{$table}].");
        }
        foreach ($added as $column) {
            $columns[$column->getName()] = $this->grammar->columnDefinition($column);
        }

        ksort($primary);
        $primary = array_values($primary);
        if (count($primary) === 1 && $autoIncrement && isset($columns[$primary[0]]) && !str_contains($columns[$primary[0]], 'PRIMARY KEY')) {
            $columns[$primary[0]] = $this->quote($primary[0]) . ' INTEGER PRIMARY KEY AUTOINCREMENT';
            $primary = [];
        }

        // Foreign keys: existing ones not dropped and not on dropped columns, then new ones.
        $constraints = [];
        if ($primary !== []) {
            $constraints[] = 'PRIMARY KEY (' . implode(', ', array_map($this->quote(...), $primary)) . ')';
        }
        $droppedForeign = $blueprint->getDropForeignKeys();
        foreach ($this->foreignKeys($table, $definition) as $key) {
            if (in_array($key['name'], $droppedForeign, true) || in_array($key['from'], $dropped, true)) {
                continue;
            }
            $from = $renames[$key['from']] ?? $key['from'];
            $sql = ($key['name'] !== null ? 'CONSTRAINT ' . $this->quote($key['name']) . ' ' : '')
                . 'FOREIGN KEY (' . $this->quote($from) . ') REFERENCES ' . $this->quote($key['table']) . ' (' . $this->quote($key['to']) . ')';
            if ($key['on_delete'] !== 'NO ACTION') {
                $sql .= ' ON DELETE ' . $key['on_delete'];
            }
            if ($key['on_update'] !== 'NO ACTION') {
                $sql .= ' ON UPDATE ' . $key['on_update'];
            }
            $constraints[] = $sql;
        }
        foreach ($blueprint->getForeignKeys() as $foreignKey) {
            $constraints[] = $this->grammar->foreignKeyDefinition($foreignKey);
        }

        // Indexes: existing ones not dropped and not on dropped columns, then new ones.
        $indexes = [];
        $droppedIndexes = $blueprint->getDropIndexes();
        foreach ($this->indexes($table) as $index) {
            if (in_array($index['name'], $droppedIndexes, true) || array_intersect($index['columns'], $dropped) !== []) {
                continue;
            }
            $name = $index['automatic']
                ? IndexDefinition::defaultName(IndexType::Unique, $table, array_map(fn (string $column): string => $renames[$column] ?? $column, $index['columns']))
                : $index['name'];
            $indexes[] = sprintf(
                'CREATE %sINDEX %s ON %s (%s)',
                $index['unique'] ? 'UNIQUE ' : '',
                $this->quote($name),
                $this->quote($table),
                implode(', ', array_map(fn (string $column): string => $this->quote($renames[$column] ?? $column), $index['columns'])),
            );
        }
        foreach ($blueprint->getIndexes() as $index) {
            $indexes[] = sprintf(
                'CREATE %sINDEX %s ON %s (%s)',
                $index->getType() === IndexType::Unique ? 'UNIQUE ' : '',
                $this->quote($index->getName()),
                $this->quote($table),
                implode(', ', array_map($this->quote(...), $index->getColumns())),
            );
        }

        $enforced = self::number($this->rows('PRAGMA foreign_keys')[0] ?? [], 'foreign_keys') === 1;
        $inTransaction = $this->connection->inTransaction();
        if ($enforced && $inTransaction) {
            $referencing = $this->referencing($table);
            if ($referencing !== []) {
                throw new LogicException(sprintf(
                    'Cannot rebuild [%s] inside a transaction while foreign keys are enforced: [%s] reference it, and '
                    . 'SQLite can only stop enforcing them outside a transaction. Run this change outside a transaction '
                    . '(in a migration, return false from withinTransaction()).',
                    $table,
                    implode(', ', $referencing),
                ));
            }
        }

        $new = '__denosys_rebuild_' . $table;
        $rebuild = function () use ($table, $new, $columns, $constraints, $copy, $indexes): void {
            $this->connection->statement(sprintf('CREATE TABLE %s (%s)', $this->quote($new), implode(', ', [...array_values($columns), ...$constraints])));
            $this->connection->statement(sprintf(
                'INSERT INTO %s (%s) SELECT %s FROM %s',
                $this->quote($new),
                implode(', ', array_map($this->quote(...), array_keys($copy))),
                implode(', ', array_map($this->quote(...), array_values($copy))),
                $this->quote($table),
            ));
            $this->connection->statement('DROP TABLE ' . $this->quote($table));
            $this->connection->statement('ALTER TABLE ' . $this->quote($new) . ' RENAME TO ' . $this->quote($table));
            foreach ($indexes as $sql) {
                $this->connection->statement($sql);
            }

            $violations = $this->rows('PRAGMA foreign_key_check');
            if ($violations !== []) {
                throw new LogicException("Rebuilding [{$table}] would leave " . count($violations) . ' row(s) violating a foreign key.');
            }
        };

        if (!$enforced || $inTransaction) {
            $this->connection->transaction($rebuild);

            return;
        }

        $this->connection->statement('PRAGMA foreign_keys = OFF');
        try {
            $this->connection->transaction($rebuild);
        } finally {
            $this->connection->statement('PRAGMA foreign_keys = ON');
        }
    }

    /**
     * The other tables with a foreign key to $table.
     *
     * @return list<string>
     */
    private function referencing(string $table): array
    {
        $tables = [];
        foreach ($this->rows("SELECT name FROM sqlite_master WHERE type = 'table' AND name != ?", [$table]) as $row) {
            foreach ($this->rows('PRAGMA foreign_key_list(' . $this->quote(self::text($row, 'name')) . ')') as $key) {
                if (strcasecmp(self::text($key, 'table'), $table) === 0) {
                    $tables[] = self::text($row, 'name');
                    break;
                }
            }
        }

        return $tables;
    }

    private function tableSql(string $table): string
    {
        $sql = $this->rows("SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?", [$table])[0]['sql'] ?? null;
        if (!is_string($sql)) {
            throw new LogicException("Table [{$table}] does not exist.");
        }

        return $sql;
    }

    /**
     * The table's foreign keys, with their constraint names where the
     * definition gives one.
     *
     * @return list<array{name: string|null, from: string, table: string, to: string, on_update: string, on_delete: string}>
     */
    private function foreignKeys(string $table, string $definition): array
    {
        $names = [];
        preg_match_all('/CONSTRAINT\s+"((?:[^"]|"")+)"\s+FOREIGN\s+KEY\s*\(\s*"((?:[^"]|"")+)"\s*\)/i', $definition, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $names[str_replace('""', '"', $match[2])] = str_replace('""', '"', $match[1]);
        }

        $keys = [];
        foreach ($this->rows('PRAGMA foreign_key_list(' . $this->quote($table) . ')') as $row) {
            $from = self::text($row, 'from');
            $keys[] = [
                'name' => $names[$from] ?? null,
                'from' => $from,
                'table' => self::text($row, 'table'),
                'to' => (self::text($row, 'to') ?: 'id'),
                'on_update' => strtoupper(self::text($row, 'on_update')),
                'on_delete' => strtoupper(self::text($row, 'on_delete')),
            ];
        }

        return $keys;
    }

    /**
     * Indexes created with CREATE INDEX, and those SQLite makes for an inline
     * UNIQUE constraint ("automatic": they cannot be dropped, and are
     * recreated as named unique indexes). Primary key indexes are left out.
     *
     * @return list<array{name: string, unique: bool, automatic: bool, columns: list<string>}>
     */
    private function indexes(string $table): array
    {
        $indexes = [];
        foreach ($this->rows('PRAGMA index_list(' . $this->quote($table) . ')') as $row) {
            if (self::text($row, 'origin') === 'pk') {
                continue;
            }
            $columns = [];
            foreach ($this->rows('PRAGMA index_info(' . $this->quote(self::text($row, 'name')) . ')') as $column) {
                if (($column['name'] ?? null) === null) {
                    throw new LogicException("Cannot rebuild [{$table}]: index [" . self::text($row, 'name') . "] uses an expression.");
                }
                $columns[self::number($column, 'seqno')] = self::text($column, 'name');
            }
            ksort($columns);
            $indexes[] = [
                'name' => self::text($row, 'name'),
                'unique' => self::number($row, 'unique') === 1,
                'automatic' => self::text($row, 'origin') === 'u',
                'columns' => array_values($columns),
            ];
        }

        return $indexes;
    }

    /**
     * @param list<mixed> $bindings
     * @return list<array<mixed>>
     */
    private function rows(string $sql, array $bindings = []): array
    {
        return array_values(array_map(static fn (object $row): array => (array) $row, $this->connection->select($sql, $bindings)));
    }

    /**
     * @param array<mixed> $row
     */
    private static function text(array $row, string $key): string
    {
        $value = $row[$key] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * @param array<mixed> $row
     */
    private static function number(array $row, string $key): int
    {
        $value = $row[$key] ?? null;

        return is_numeric($value) ? (int) $value : 0;
    }

    private function quote(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }
}
