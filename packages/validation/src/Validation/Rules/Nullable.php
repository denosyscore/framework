<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Denosys\Validation\Rules\RuleInterface;

class Nullable implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        return true; // Nullable is handled specially
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return '';
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return [];
    }

    public static function ruleName(): string
    {
        return 'nullable';
    }
}
