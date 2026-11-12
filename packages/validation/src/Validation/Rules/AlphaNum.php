<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Denosys\Validation\Rules\RuleInterface;

class AlphaNum implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        return is_string($value) && preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute may only contain letters and numbers.';
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
        return 'alpha_num';
    }
}
