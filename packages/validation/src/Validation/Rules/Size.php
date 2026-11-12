<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use SplFileInfo;
use InvalidArgumentException;

/**
 * Size validation rule
 */
class Size implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!isset($parameters[0])) {
            throw new InvalidArgumentException('Size rule requires a size parameter');
        }

        $size = $parameters[0];

        if (is_numeric($value)) {
            return $value == $size;
        }

        if (is_string($value)) {
            return mb_strlen($value) == $size;
        }

        if (is_array($value)) {
            return count($value) == $size;
        }

        if ($value instanceof SplFileInfo) {
            return $value->getSize() / 1024 == $size;
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        $size = $parameters['size'];

        return "The :attribute must be exactly $size.";
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['size'];
    }

    public static function ruleName(): string
    {
        return 'size';
    }
}
