<?php

declare(strict_types=1);

namespace Denosys\Validation;

use Countable;
use JsonSerializable;

class ErrorBag implements Countable, JsonSerializable
{
    /**
     * @param array<string, array<string>> $errors
      * @param array<string, array<string>> $errors
     */
    public function __construct(private array $errors = [])
    {
    }

    /**
     * Add an error message
     */
    public function add(string $field, string $message): void
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all errors for a field
     *
     * @return array<string>
     */
    /**
     * @return array<string, mixed>
     */
public function get(string $field): array
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Get the first error for a field
     */
    public function first(string $field): ?string
    {
        $errors = $this->get($field);

        return $errors[0] ?? null;
    }

    /**
     * Check if a field has errors
     */
    public function has(string $field): bool
    {
        return isset($this->errors[$field]) && count($this->errors[$field]) > 0;
    }

    /**
     * Check if there are any errors
     */
    public function any(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Get all errors
     *
     * @return array<string>
     */
    public function all(): array
    {
        $all = [];
        foreach ($this->errors as $field => $messages) {
            foreach ($messages as $message) {
                $all[] = $message;
            }
        }

        return $all;
    }

    /**
     * Get errors as array
     *
     * @return array<string, array<string>>
     */
    public function toArray(): array
    {
        return $this->errors;
    }

    /**
     * Count total errors
     */
    public function count(): int
    {
        $count = 0;
        foreach ($this->errors as $messages) {
            $count += count($messages);
        }

        return $count;
    }

    /**
     * JSON serialization
     *
     * @return array<string, array<string>>
     */
    public function jsonSerialize(): array
    {
        return $this->errors;
    }

    /**
     * Check if bag is empty
     */
    public function isEmpty(): bool
    {
        return count($this->errors) === 0;
    }

    /**
     * Clear all errors
     */
    public function clear(): void
    {
        $this->errors = [];
    }

    /**
     * Merge errors from another bag or array
     *
     * @param array<string, array<string>|string>|ErrorBag $errors
     */
    public function merge(array|ErrorBag $errors): void
    {
        if ($errors instanceof ErrorBag) {
            $errors = $errors->toArray();
        }

        foreach ($errors as $field => $messages) {
            foreach ((array) $messages as $message) {
                $this->add($field, $message);
            }
        }
    }
}

