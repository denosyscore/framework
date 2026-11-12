<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use InvalidArgumentException;
use Psr\Http\Message\UploadedFileInterface;
use SplFileInfo;

/**
 * Maximum value/length/size validation rule
 * 
 * Behavior depends on context:
 * - For files: validates file size in kilobytes
 * - With 'numeric' or 'integer' rule: validates numeric value
 * - With 'string' rule or by default: validates string length
 * - For arrays: validates element count
 * 
 * Usage:
 *   String syntax: 'max:100'
 *   Object syntax: new Max(100)
 */
class Max implements RuleInterface
{
    private string $sizeType = 'characters';

    /**
     * @param int|float|null $max
     */
    public function __construct(
        private int|float|null $max = null
    ) {}

    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        $max = $this->max ?? $parameters['max'] ?? null;

        if ($max === null) {
            throw new InvalidArgumentException('Max rule requires a parameter');
        }

        $max = (float) $max;

        // File uploads - check size in kilobytes
        if ($value instanceof UploadedFileInterface) {
            $this->sizeType = 'kilobytes';
            $sizeInKb = $value->getSize() / 1024;
            return $sizeInKb <= $max;
        }

        if ($value instanceof SplFileInfo) {
            $this->sizeType = 'kilobytes';
            $sizeInKb = $value->getSize() / 1024;
            return $sizeInKb <= $max;
        }

        // Check field rules context if available
        $fieldRules = $data['_field_rules'] ?? [];
        $hasNumericRule = in_array('numeric', $fieldRules, true) || in_array('integer', $fieldRules, true);

        // If 'numeric' or 'integer' rule is present, treat as number comparison
        if ($hasNumericRule && is_numeric($value)) {
            $this->sizeType = '';
            return (float) $value <= $max;
        }

        // If value is string type, compare by length
        if (is_string($value)) {
            $this->sizeType = 'characters';
            return mb_strlen($value) <= (int) $max;
        }

        // Numeric values without string rule
        if (is_numeric($value)) {
            $this->sizeType = '';
            return (float) $value <= $max;
        }

        if (is_array($value)) {
            $this->sizeType = 'items';
            return count($value) <= (int) $max;
        }

        return false;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        if ($this->sizeType === 'kilobytes') {
            return "The :attribute may not be greater than :max kilobytes.";
        }
        
        if ($this->sizeType === 'characters') {
            return "The :attribute may not be greater than :max characters.";
        }
        
        if ($this->sizeType === 'items') {
            return "The :attribute may not have more than :max items.";
        }

        return "The :attribute may not be greater than :max.";
    }

    /**
     * @return array<int, string>
     */
    public static function parameterNames(): array
    {
        return ['max'];
    }

    public static function ruleName(): string
    {
        return 'max';
    }
}
