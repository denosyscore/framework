<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

/**
 * Interface for validation rules
 */
interface RuleInterface
{
    /**
     * Validate a value against this rule. The validator binds rule arguments
     * by name; direct callers may also supply positional arguments where a
     * rule supports them. Values can be nested lists, numbers, or null.
     *
     * @param string $field
     * @param mixed $value
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     *
     * @return bool
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool;

    /**
     * Get the validation error message using the same bound parameters.
     *
     * @param string $field
     * @param array<int|string, mixed> $parameters
     *
     * @return string
     */
    public function message(string $field, array $parameters = []): string;

    /**
     * Return the list of parameter names, in order.
     *
     * @return array<int, string>
     */
    public static function parameterNames(): array;

    public static function ruleName(): string;
}

