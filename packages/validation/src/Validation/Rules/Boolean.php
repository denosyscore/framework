<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Denosys\Validation\Rules\RuleInterface;

class Boolean implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        $acceptable = [true, false, 0, 1, '0', '1'];
        return in_array($value, $acceptable, true);
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute field must be true or false.';
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
        return 'boolean';
    }
}
