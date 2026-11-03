<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use InvalidArgumentException;

class Regex implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $pattern = $parameters['pattern'] ?? $parameters[0] ?? null;

        if ($pattern === null) {
            throw new InvalidArgumentException('Regex rule requires a pattern parameter');
        }

        // Add delimiters if not present
        if (!preg_match('/^[\/#~]/', $pattern)) {
            $pattern = '/'.$pattern.'/';
        }

        return preg_match($pattern, (string) $value) === 1;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute format is invalid.';
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['pattern'];
    }

    public static function ruleName(): string
    {
        return 'regex';
    }
}
