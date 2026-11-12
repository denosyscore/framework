<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;

/**
 * Minimum value/length/size validation rule
 * 
 * Behavior depends on context:
 * - For files: validates file size in kilobytes
 * - With 'numeric' or 'integer' rule: validates numeric value
 * - With 'string' rule or by default: validates string length
 * - For arrays: validates element count
 * 
 * Usage:
 *   String syntax: 'min:10'
 *   Object syntax: new Min(10)
 */
class Min implements RuleInterface
{
    private string $sizeType = 'characters';

    /**
     * @param int|float|null $min
     */
    public function __construct(
        private int|float|null $min = null
    ) {}

    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        $min = $this->min ?? $parameters['min'] ?? $parameters[0] ?? null;
        if ($min === null) {
            throw new \InvalidArgumentException('Min rule requires a parameter');
        }

        $min = (float) $min;

        // File uploads - check size in kilobytes
        if ($value instanceof UploadedFileInterface) {
            $this->sizeType = 'kilobytes';
            $sizeInKb = $value->getSize() / 1024;
            return $sizeInKb >= $min;
        }

        if ($value instanceof SplFileInfo) {
            $this->sizeType = 'kilobytes';
            $sizeInKb = $value->getSize() / 1024;
            return $sizeInKb >= $min;
        }

        // Check field rules context if available
        $fieldRules = $data['_field_rules'] ?? [];
        $hasNumericRule = in_array('numeric', $fieldRules, true) || in_array('integer', $fieldRules, true);

        // If 'numeric' or 'integer' rule is present, treat as number comparison
        if ($hasNumericRule && is_numeric($value)) {
            $this->sizeType = '';
            return (float) $value >= $min;
        }

        // If value is string type, compare by length
        if (is_string($value)) {
            $this->sizeType = 'characters';
            return mb_strlen($value) >= (int) $min;
        }

        // Numeric values without string rule
        if (is_numeric($value)) {
            $this->sizeType = '';
            return (float) $value >= $min;
        }

        if (is_array($value)) {
            $this->sizeType = 'items';
            return count($value) >= (int) $min;
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        if ($this->sizeType === 'kilobytes') {
            return "The :attribute must be at least :min kilobytes.";
        }
        
        if ($this->sizeType === 'characters') {
            return "The :attribute must be at least :min characters.";
        }
        
        if ($this->sizeType === 'items') {
            return "The :attribute must have at least :min items.";
        }

        return "The :attribute must be at least :min.";
    }

    /**
     * @return array<int, string>
     */
    public static function parameterNames(): array
    {
        return ['min'];
    }

    public static function ruleName(): string
    {
        return 'min';
    }
}
