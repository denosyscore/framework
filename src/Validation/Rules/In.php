<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

/**
 * In array validation rule
 * 
 * Usage:
 *   String syntax: 'in:value1,value2,value3'
 *   Object syntax: new In('value1', 'value2', 'value3')
 */
class In implements RuleInterface
{
    /** @var array<string>|null */
    private ?array $allowedValues = null;

    /**
     * @param string ...$values Allowed values
     */
    public function __construct(string ...$values)
    {
        if (count($values) > 0) {
            $this->allowedValues = $values;
        }
    }

    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        // Use constructor-provided values first, then fall back to parameters
        $values = $this->allowedValues ?? $parameters['values'] ?? $parameters;
        
        // If values is a string (comma-separated), split it
        if (is_string($values)) {
            $values = array_map('trim', explode(',', $values));
        }
        
        // Ensure we have an array
        if (!is_array($values)) {
            return false;
        }

        return in_array($value, $values, true);
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        $values = $parameters['values'] ?? $parameters;
        
        if (is_string($values)) {
            $list = $values;
        } elseif (is_array($values)) {
            $list = implode(', ', $values);
        } else {
            $list = '';
        }
        
        return "The :attribute must be one of: {$list}.";
    }

    /**
     * @return array<int, string>
     */
    public static function parameterNames(): array
    {
        return ['values'];
    }

    public static function ruleName(): string
    {
        return 'in';
    }
}
