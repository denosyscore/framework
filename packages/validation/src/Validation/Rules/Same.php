<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use InvalidArgumentException;

/**
 * Same as another field validation rule
 */
class Same implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!isset($parameters[0])) {
            throw new InvalidArgumentException('Same rule requires a field parameter');
        }

        $otherField = $parameters[0];

        if (!isset($data[$otherField])) {
            return false;
        }

        return $value === $data[$otherField];
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        $otherField = $parameters['other'];

        return "The :attribute and $otherField must match.";
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['other'];
    }

    public static function ruleName(): string
    {
        return 'same';
    }
}
