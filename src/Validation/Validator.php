<?php

declare(strict_types=1);

namespace Denosys\Validation;

use Denosys\Validation\Rules\RuleInterface;
use InvalidArgumentException;

/**
 * Main validation class
 */
class Validator
{
    /**
     * @param array<string, mixed> $ruleInstances
     * @param array<string, mixed> $validatedData
     * @param array<string, mixed> $failedRules
     * @param array<string, mixed> $afterCallbacks
     */
    private ErrorBag $errorBag;

    /** @var array<string, RuleInterface> */
    private array $ruleInstances = [];

    /**
     * @param array<string, mixed> $validatedData
     * @param array<string, mixed> $failedRules
     * @param array<string, mixed> $afterCallbacks
     */
    private bool $validated = false;

    /** @var array<string, mixed> */
    private array $validatedData = [];

    /** @var array<string, array<string>> */
    private array $failedRules = [];

    /** @var bool Stop on first validation failure */
    private bool $stopOnFirstFailure = false;

    /** @var array<callable> After-validation callbacks */
    private array $afterCallbacks = [];

    /** @var array<string, class-string<RuleInterface>|callable():RuleInterface> */
    private static array $registeredRules = [];

    /** @var array<string, callable> Custom validation callbacks */
    private static array $customCallbacks = [];

    /**
     * @param array<string,mixed> $data
     * @param array<string, string|array<string|RuleInterface>> $rules
     * @param array<string, string> $customMessages
     * @param array<string, string> $customAttributes
      * @param array<string, mixed> $rules
     */
    public function __construct(
        /**
         * @param array<string, mixed> $data
         * @param array<string, mixed> $rules
         * @param array<string, mixed> $customMessages
         * @param array<string, mixed> $customAttributes
         */
        private readonly array $data,
        /**
         * @param array<string, mixed> $rules
         * @param array<string, mixed> $customMessages
         * @param array<string, mixed> $customAttributes
         */
        private readonly array $rules,
        /**
         * @param array<string, mixed> $customMessages
         * @param array<string, mixed> $customAttributes
         */
        private array $customMessages = [],
        /**
         * @param array<string, mixed> $customAttributes
         */
        private array $customAttributes = []
    ) {
        $this->errorBag = new ErrorBag();
    }

    /**
     * @template T of array
     * @param T $data
     * @param array<string, string|array<string|RuleInterface>> $rules
     * @param array<string, string> $customMessages
     * @param array<string, string> $customAttributes
     */
    public static function make(
        array $data,
        array $rules,
        array $customMessages = [],
        array $customAttributes = []
    ): self {
        return new self($data, $rules, $customMessages, $customAttributes);
    }

    /**
    * Register a custom validation callback
    */
    public static function extendCallback(string $ruleName, callable $callback): void
    {
        self::$customCallbacks[$ruleName] = $callback;
    }

    /**
     * Register a custom validation rule
     *
     * @param class-string<RuleInterface>|callable():RuleInterface $factory
     */
    public static function extend(string $ruleName, string|callable $factory): void
    {
        if (is_string($factory) && !class_exists($factory)) {
            throw new InvalidArgumentException("Rule class [$factory] does not exist");
        }

        self::$registeredRules[$ruleName] = $factory;
    }

    /**
     * Whether a rule name resolves: registered with extend() or shipped with the package.
     */
    public static function hasRule(string $ruleName): bool
    {
        return isset(self::$registeredRules[$ruleName]) || isset(BuiltInRules::MAP[$ruleName]);
    }

    /**
     * Restore default rule registry (and clear custom callbacks)
     */
    public static function reset(): void
    {
        self::$registeredRules = [];
        self::$customCallbacks = [];
    }

    /**
     * Run validation.
     */
    public function validate(): bool
    {
        if ($this->validated) {
            return !$this->errorBag->any();
        }

        $this->errorBag->clear();
        $this->validatedData = [];
        $this->failedRules = [];

        foreach ($this->rules as $pattern => $fieldRules) {
            $targets = $this->expandWildcardTargets($pattern);

            if (empty($targets) && !str_contains($pattern, '*')) {
                $targets = [$pattern];
            }

            foreach ($targets as $field) {
                $this->validateField($field, $fieldRules, $pattern);

                // Stop on first failure if configured
                if ($this->stopOnFirstFailure && $this->errorBag->any()) {
                    break 2;
                }
            }
        }

        $this->validated = true;

        // Run after-validation callbacks
        foreach ($this->afterCallbacks as $callback) {
            $callback($this);
        }

        return !$this->errorBag->any();
    }

    /**
     * Validate a single field (after wildcard expansion)
     * @param string $field   Concrete path (e.g., users.0.email)
     * @param array<string|RuleInterface>|string $rules
     * @param string $originalPattern For custom message/attribute lookups
     */
    private function validateField(string $field, array|string $rules, string $originalPattern): void
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $value = $this->getValue($field);
        $bail = in_array('bail', $rules, true);
        $nullable = in_array('nullable', $rules, true);

        $hasSometimes = in_array('sometimes', $rules, true);
        if ($hasSometimes && !$this->hasKey($field)) {
            return;
        }

        if (in_array('present', $rules, true) && !$this->hasKey($field)) {
            $message = $this->getErrorMessage($field, 'present', [], $originalPattern);
            $this->errorBag->add($field, $message);
            $this->failedRules[$field][] = 'present';

            if ($bail) {
                return;
            }
        }

        // Store validated data if the key exists
        if ($this->hasKey($field)) {
            $this->setNestedValue($this->validatedData, $field, $value);
        }

        foreach ($rules as $rule) {
            if (in_array($rule, ['bail', 'sometimes', 'present', 'nullable'], true)) {
                continue;
            }

            // Skip validation if nullable and value is null
            if ($nullable && $value === null) {
                continue;
            }

            $errorCountBefore = $this->errorBag->count();

            if (is_string($rule)) {
                $this->validateRule($field, $value, $rule, $originalPattern, $rules);
            } elseif ($rule instanceof RuleInterface) {
                if (!$this->shouldSkipRule($rule, $value, $nullable)) {
                    if (!$rule->validate($field, $value, [], $this->data)) {
                        $message = $this->replaceParameters($rule->message($field), $field, [], $originalPattern);
                        $this->errorBag->add($field, $message);
                        $this->failedRules[$field][] = get_class($rule);
                    }
                }
            }

            if ($bail && $this->errorBag->count() > $errorCountBefore) {
                break;
            }
        }
    }

    /**
     * Validate a single rule
     * @param array<string|RuleInterface> $fieldRules All rules for this field (for context-aware rules)
     */
    private function validateRule(string $field, mixed $value, string $rule, string $originalPattern, array $fieldRules = []): void
    {
        [$ruleName, $parameters] = $this->parseRule($rule);

        if (isset(self::$customCallbacks[$ruleName])) {
            $named = $this->bindNamedParameters($this->getRuleInstance($ruleName), $parameters);
            $callback = self::$customCallbacks[$ruleName];

            if (!$callback($field, $value, $named, $this->data)) {
                $message = $this->getErrorMessage($field, $ruleName, $named, $originalPattern);
                $this->errorBag->add($field, $message);
                $this->failedRules[$field][] = $ruleName;
            }

            return;
        }

        if ($this->isEmpty($value)) {
            $shouldValidateEmpty = in_array($ruleName, [
                'required',
                'filled',
                'confirmed',
                'present',
                'nullable'
            ]);

            if (!$shouldValidateEmpty) {
                return;
            }
        }

        $ruleInstance = $this->getRuleInstance($ruleName);
        $named = $this->bindNamedParameters($ruleInstance, $parameters);

        // Pass field rules context for size-aware rules (max, min, between, size)
        $dataWithContext = $this->data;
        $dataWithContext['_field_rules'] = $this->extractRuleNames($fieldRules);

        if (!$ruleInstance->validate($field, $value, $named, $dataWithContext)) {
            $message = $this->getErrorMessage($field, $ruleName, $named, $originalPattern);
            $this->errorBag->add($field, $message);
            $this->failedRules[$field][] = $ruleName;
        }
    }

    /**
     * Determine if a rule should be skipped
     */
    private function shouldSkipRule(RuleInterface $rule, mixed $value, bool $nullable): bool
    {
        if ($nullable && null === $value) {
            return true;
        }

        $requiredRules = ['required', 'filled'];
        $isRequiredRule = false;

        foreach ($requiredRules as $requiredRule) {
            if ($this->isRuleName($rule, $requiredRule)) {
                $isRequiredRule = true;
                break;
            }
        }

        return !$isRequiredRule && $this->isEmpty($value);
    }

    /**
     * Parse rule string into name and parameters
     *
     * @return array{0:string,1:array<int|string,mixed>}
     */
    /**
     * @return array<string, mixed>
     */
private function parseRule(string $rule): array
    {
        [$name, $paramString] = array_pad(explode(':', $rule, 2), 2, null);

        if ($paramString === null) {
            return [$name, []];
        }

        if ($name === 'regex') {
            return [$name, [$paramString]];
        }

        $params = array_map('trim', explode(',', $paramString));

        $params = array_map(function ($param) {
            if (is_numeric($param)) {
                return $param + 0;
            }

            $lower = strtolower($param);

            if ($lower === 'true') {
                return true;
            }

            if ($lower === 'false') {
                return false;
            }

            if ($lower === 'null') {
                return null;
            }

            return $param;
        }, $params);

        return [$name, $params];
    }

    /**
     * @param  string  $ruleName
     *
     * @return RuleInterface
     */
    private function getRuleInstance(string $ruleName): RuleInterface
    {
        if (isset($this->ruleInstances[$ruleName])) {
            return $this->ruleInstances[$ruleName];
        }

        $factory = self::$registeredRules[$ruleName] ?? BuiltInRules::MAP[$ruleName] ?? null;

        if ($factory === null) {
            throw new InvalidArgumentException("Validation rule '$ruleName' does not exist");
        }

        $instance = is_string($factory) ? new $factory() : $factory();

        if (!$instance instanceof RuleInterface) {
            throw new InvalidArgumentException("Rule '$ruleName' must implement RuleInterface");
        }

        return $this->ruleInstances[$ruleName] = $instance;
    }

    /**
     * Get error message for a rule
      * @param array<int, mixed> $parameters
     */
    private function getErrorMessage(
        string $field,
        string $ruleName,
        array $parameters,
        string $originalPattern
    ): string {
        $customKeys = [];

        if ($originalPattern !== $field) {
            $customKeys[] = "$originalPattern.$ruleName";
        }

        $customKeys[] = "$field.$ruleName";

        $customKeys[] = $ruleName;

        foreach ($customKeys as $key) {
            if (isset($this->customMessages[$key])) {
                return $this->replaceParameters($this->customMessages[$key], $field, $parameters, $originalPattern);
            }
        }

        if (isset(self::$customCallbacks[$ruleName])) {
            return $this->replaceParameters(
                "The :attribute field failed the $ruleName validation.",
                $field,
                $parameters,
                $originalPattern
            );
        }

        $ruleInstance = $this->getRuleInstance($ruleName);

        return $this->replaceParameters(
            $ruleInstance->message($field, $parameters),
            $field,
            $parameters,
            $originalPattern
        );
    }

    /**
     * Replace parameters and :attribute with friendly name
     * Accepts named or positional parameters
     *
     * @param array<int|string,mixed> $parameters
     */
    private function replaceParameters(string $message, string $field, array $parameters, ?string $originalPattern = null): string
    {
        $attribute = $this->friendlyAttribute($field, $originalPattern);
        $message = str_replace(':attribute', $attribute, $message);

        foreach ($parameters as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            }

            // If the parameter value looks like a field reference (string that exists in data
            // or has a custom attribute), resolve it to its friendly name
            if (is_string($value) && $this->isFieldReference($value)) {
                $value = $this->friendlyAttribute($value);
            }

            $message = str_replace(':' . $key, (string) $value, $message);
        }

        return $message;
    }

    /**
     * Check if a value appears to be a reference to another field
     */
    private function isFieldReference(string $value): bool
    {
        return isset($this->customAttributes[$value])
            || array_key_exists($value, $this->data)
            || str_contains($value, '.');
    }

    private function friendlyAttribute(string $field, ?string $originalPattern = null): string
    {
        if (isset($this->customAttributes[$field])) {
            return $this->customAttributes[$field];
        }

        if ($originalPattern && isset($this->customAttributes[$originalPattern])) {
            return $this->customAttributes[$originalPattern];
        }

        // Convert snake_case and dots to spaces
        $attribute = str_replace(['_', '.'], ' ', $field);

        // Remove array indices from attribute names
        $attribute = preg_replace('/\s\d+\s/', ' ', $attribute);

        return trim($attribute);
    }

    /**
     * @param array<int, mixed> $positional
     * @return array<string, mixed>
     */
    private function bindNamedParameters(object $ruleInstance, array $positional): array
    {
        $class = get_class($ruleInstance);
        $names = $class::parameterNames();

        if (count($names) === 1) {
            $key = $names[0];
            return [$key => (count($positional) <= 1 ? ($positional[0] ?? null) : $positional)];
        }

        $named = [];
        foreach ($names as $i => $name) {
            $named[$name] = $positional[$i] ?? null;
        }
        return $named;
    }

    /**
     * Get value from data using dot notation
     */
    private function getValue(string $field): mixed
    {
        if (!str_contains($field, '.')) {
            return $this->data[$field] ?? null;
        }

        return $this->getNestedValue($field);
    }

    /**
     * Get nested value using dot notation
     */
    private function getNestedValue(string $field): mixed
    {
        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return null;
            }
            $value = $value[$key];
        }

        return $value;
    }

    private function hasKey(string $field): bool
    {
        if (!str_contains($field, '.')) {
            return array_key_exists($field, $this->data);
        }

        $keys = explode('.', $field);
        $value = $this->data;

        foreach ($keys as $key) {
            if (!is_array($value) || !array_key_exists($key, $value)) {
                return false;
            }
            $value = $value[$key];
        }

        return true;
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null
            || (is_string($value) && trim($value) === '')
            || (is_array($value) && count($value) === 0);
    }

    /**
     * Check if a rule instance "is" a certain name (for required check on objects)
     */
    private function isRuleName(RuleInterface $rule, string $name): bool
    {
        $class = get_class($rule);

        // Check direct class mapping
        $factory = self::$registeredRules[$name] ?? BuiltInRules::MAP[$name] ?? null;
        if (is_string($factory)) {
            return $factory === $class;
        }

        // Check if the class name ends with the rule name
        return str_ends_with($class, '\\' . ucfirst($name));
    }

    /**
     * Extract rule names from a mixed array of rules
     *
     * @param array<string|RuleInterface> $rules
     * @return array<string>
     */
    private function extractRuleNames(array $rules): array
    {
        $names = [];
        foreach ($rules as $rule) {
            if (is_string($rule)) {
                // Get rule name before any parameters (e.g., "max:100" -> "max")
                $parts = explode(':', $rule, 2);
                $names[] = $parts[0];
            } elseif ($rule instanceof RuleInterface) {
                $names[] = $rule::ruleName();
            }
        }
        return $names;
    }

    /**
     * Expand wildcard patterns to concrete field paths
     * E.G., users.*.email -> ['users.0.email', 'users.1.email', ...]
     *      meta.* -> each key inside meta
     * If any segment with '*' is not an array at runtime, yields [] for that branch.
     *
     * @return list<string>
     */
    private function expandWildcardTargets(string $pattern): array
    {
        if (!str_contains($pattern, '*')) {
            return [$pattern];
        }

        $segments = explode('.', $pattern);
        $paths = [''];
        $nodes = [$this->data];

        foreach ($segments as $segment) {
            $nextPaths = [];
            $nextNodes = [];

            foreach ($paths as $index => $basePath) {
                $node = $nodes[$index];

                if ($segment === '*') {
                    if (!is_array($node)) {
                        continue;
                    }
                    foreach ($node as $key => $child) {
                        $nextPaths[] = $basePath === '' ? (string) $key : $basePath . '.' . $key;
                        $nextNodes[] = $child;
                    }
                } else {
                    $child = (is_array($node) && array_key_exists($segment, $node)) ? $node[$segment] : null;
                    $nextPaths[] = $basePath === '' ? $segment : $basePath . '.' . $segment;
                    $nextNodes[] = $child;
                }
            }

            $paths = $nextPaths;
            $nodes = $nextNodes;

            if (empty($paths)) {
                break;
            }
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $array
     */
    private function setNestedValue(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $index => $keyItem) {
            if ($index === count($keys) - 1) {
                $current[$keyItem] = $value;
            } else {
                if (!isset($current[$keyItem]) || !is_array($current[$keyItem])) {
                    $current[$keyItem] = [];
                }
                $current = &$current[$keyItem];
            }
        }
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return $this->validate();
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->validate();
    }

    /**
    * Get error bag
    */
    public function errors(): ErrorBag
    {
        $this->validate();
        return $this->errorBag;
    }

    /**
     * Get all errors as an array
      * @return array<string, array<string>>
     */
    public function getErrors(): array
    {
        $this->validate();
        return $this->errorBag->toArray();
    }

    /**
     * Get failed rules.
     *
     * @return array<string, array<string>>
     */
    public function failed(): array
    {
        $this->validate();
        return $this->failedRules;
    }

    /**
     * Get the first error message for a field
     */
    public function first(string $field): ?string
    {
        $this->validate();
        return $this->errorBag->first($field);
    }

    /**
     * Get validated data (only fields that were validated; includes nested and wildcards)
     *
     * @throws ValidationException
     * @return array<string,mixed>
     */
    public function validated(): array
    {
        if (!$this->passes()) {
            throw new ValidationException($this);
        }

        return $this->validatedData;
    }

    /**
     * Get safe data (validated data or all data if validation hasn't run).
     *
     * @return array<string, mixed>
     */
    public function safe(): array
    {
        $this->validate();
        return $this->validatedData;
    }

    /**
     * Throw a validation exception if validation fails
     *
     * @throws ValidationException
     * @return array<string,mixed>
     */
    public function validateOrFail(): array
    {
        if ($this->fails()) {
            throw new ValidationException($this);
        }

        return $this->validated();
    }

    /**
     * Add after-validation callback.
     * 
     * Callbacks are executed after validation runs, receiving the Validator instance.
     */
    public function after(callable $callback): self
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    /**
     * Stop validation on first failure.
     * 
     * Similar to adding 'bail' to every rule, but applied globally.
     */
    public function stopOnFirstFailure(bool $stop = true): self
    {
        $this->stopOnFirstFailure = $stop;
        return $this;
    }

    /**
     * Set custom error messages
      * @param array<string, string> $messages
     */
    public function messages(array $messages): self
    {
        foreach ($messages as $key => $message) {
            $this->customMessages[$key] = $message;
        }

        return $this;
    }

    /**
     * Set custom attributes
      * @param array<string, mixed> $attributes
     */
    public function attributes(array $attributes): self
    {
        foreach ($attributes as $key => $attribute) {
            $this->customAttributes[$key] = $attribute;
        }

        return $this;
    }
}
