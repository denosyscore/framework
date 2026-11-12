<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use InvalidArgumentException;

/**
 * Between validation rule
 */
class Between implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!array_key_exists('min', $parameters) || !array_key_exists('max', $parameters)) {
            throw new InvalidArgumentException('Between rule requires min and max parameters');
        }

        $min = $parameters['min'];
        $max = $parameters['max'];

        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);

            return $length >= $min && $length <= $max;
        }

        if (is_array($value)) {
            $count = count($value);

            return $count >= $min && $count <= $max;
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return "The :attribute must be between :min and :max.";
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['min', 'max'];
    }

    public static function ruleName(): string
    {
        return 'between';
    }
}
