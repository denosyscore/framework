<?php

declare(strict_types=1);

use Denosys\Validation\Validator;
use Denosys\Validation\ErrorBag;

if (!function_exists('validate')) {
    /**
     * Create a validator instance
     *
     * @param array<string, mixed> $data
     * @param array<string, string|array<string>> $rules
     * @param array<string, string> $messages
     * @param array<string, string> $attributes
     * @return array<string, mixed>
     * @throws \Denosys\Validation\ValidationException
     */
    function validate(array $data, array $rules, array $messages = [], array $attributes = []): array
    {
        $validator = new Validator($data, $rules, $messages, $attributes);

        return $validator->validateOrFail();
    }
}

if (!function_exists('errors')) {
    /**
     * Get validation errors from session flash data.
     */
    function errors(): ErrorBag
    {
        // Try to get from session flash data first
        $session = session();
        if ($session !== null) {
            $errors = $session->getFlash('errors', []);
            if (!empty($errors)) {
                return new ErrorBag($errors);
            }
        }
        
        // Fall back to globals (for testing)
        return $GLOBALS['validation_errors'] ?? new ErrorBag();
    }
}

if (!function_exists('old')) {
    /**
     * Get old input value from session flash data.
     */
    function old(string $key, mixed $default = null): mixed
    {
        // Try to get from session flash data first
        $session = session();
        if ($session !== null) {
            $oldInput = $session->getFlash('old', []);
            if (isset($oldInput[$key])) {
                return $oldInput[$key];
            }
        }
        
        // Fall back to globals (for testing)
        $oldInput = $GLOBALS['old_input'] ?? [];
        return $oldInput[$key] ?? $default;
    }
}

if (!function_exists('class_names')) {
    /**
     * Build class string from array of conditions
     *
     * @param array<string|int, string|bool> $classes
     */
    function class_names(array $classes): string
    {
        $result = [];

        foreach ($classes as $class => $condition) {
            if (is_numeric($class)) {
                // If numeric key, the value is the class name (always include)
                $result[] = $condition;
            } elseif ($condition) {
                // If associative, include class if condition is truthy
                $result[] = $class;
            }
        }

        return implode(' ', $result);
    }
}

if (!function_exists('validation_errors')) {
    /**
     * Set validation errors globally (for testing)
     *
     * @param array<string, array<string>> $errors
     */
    function validation_errors(array $errors): void
    {
        $GLOBALS['validation_errors'] = new ErrorBag($errors);
    }
}

if (!function_exists('old_input')) {
    /**
     * Set old input globally (for testing)
     *
     * @param array<string, mixed> $input
     */
    function old_input(array $input): void
    {
        $GLOBALS['old_input'] = $input;
    }
}

