<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use InvalidArgumentException;

/**
 * Different from another field validation rule
 */
class Different implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!array_key_exists('other_field', $parameters)) {
            throw new InvalidArgumentException('Different rule requires a field parameter');
        }

        $otherField = $parameters['other_field'];

        if (!isset($data[$otherField])) {
            return true;
        }

        return $value !== $data[$otherField];
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return "The :attribute and :other_field must be different.";
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['other_field'];
    }

    public static function ruleName(): string
    {
        return 'different';
    }
}
