<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use BackedEnum;
use ReflectionEnum;

/**
 * Enum validation rule
 * 
 * Validates that a value is a valid case of a backed enum.
 * 
 * Usage: 
 *   String syntax: 'enum:App\Enums\Status'
 *   Object syntax: new Enum(Status::class)
 * 
 * @example
 * 'status' => ['required', 'enum:App\Enums\Status']
 * 'status' => ['required', new Enum(Status::class)]
 */
class Enum implements RuleInterface
{
    /**
     * @param class-string<BackedEnum>|null $enumClass
     */
    public function __construct(
        private ?string $enumClass = null
    ) {}

    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        $enumClass = $this->enumClass ?? $parameters['enum'] ?? $parameters[0] ?? null;
        
        if ($enumClass === null) {
            return false;
        }

        // Check if the class exists and is an enum
        if (!class_exists($enumClass) && !enum_exists($enumClass)) {
            return false;
        }

        // Check if it's a backed enum
        if (!is_subclass_of($enumClass, BackedEnum::class)) {
            return false;
        }

        // Get all valid values from the enum
        $validValues = array_column($enumClass::cases(), 'value');

        return in_array($value, $validValues, true);
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute must be a valid option.';
    }

    /**
     * @return array<int, string>
     */
    public static function parameterNames(): array
    {
        return ['enum'];
    }

    public static function ruleName(): string
    {
        return 'enum';
    }
}
