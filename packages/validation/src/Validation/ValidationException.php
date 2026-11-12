<?php

declare(strict_types=1);

namespace Denosys\Validation;

use Exception;
use InvalidArgumentException;
use Throwable;

/**
 * Exception thrown when validation fails
 */
class ValidationException extends Exception
{
    public function __construct(
        public readonly Validator $validator,
        int $code = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $this->summarizeErrorMessages($validator),
            $code,
            $previous
        );
    }

    /**
     * Create an exception for field errors discovered outside rule validation.
     *
     * @param array<string, string|list<string>> $messages
     */
    public static function withMessages(array $messages): self
    {
        if ($messages === []) {
            throw new InvalidArgumentException('Validation messages must not be empty.');
        }

        $validator = Validator::make([], []);
        $validator->validate();

        foreach ($messages as $field => $fieldMessages) {
            if (!is_string($field) || $field === '') {
                throw new InvalidArgumentException('Validation message fields must be non-empty strings.');
            }

            if (is_string($fieldMessages)) {
                $fieldMessages = [$fieldMessages];
            }

            if (!is_array($fieldMessages) || !array_is_list($fieldMessages) || $fieldMessages === []) {
                throw new InvalidArgumentException('Validation field messages must be strings or non-empty lists of strings.');
            }

            foreach ($fieldMessages as $message) {
                if (!is_string($message) || $message === '') {
                    throw new InvalidArgumentException('Validation messages must be non-empty strings.');
                }

                $validator->errors()->add($field, $message);
            }
        }

        return new self($validator);
    }

    /**
     * Get validation errors
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /**
     * @return array<array<string>>
     */
    /** @return array<string, array<string>> */
    public function getErrors(): array
    {
        return $this->validator->getErrors();
    }

    /**
     * Get the first error message for a field
     */
    public function getFirstError(?string $field = null): ?string
    {
        if ($field === null) {
            return $this->validator->errors()->all()[0] ?? null;
        }

        return $this->validator->first($field);
    }

    private function summarizeErrorMessages(Validator $validator): string
    {
        $messages = $validator->errors()->all();

        if (empty($messages) || !is_string($messages[0])) {
            return 'Validation failed.';
        }

        $total = count($messages);
        $first = array_shift($messages);
        $others = $total - 1;

        if ($others > 0) {
            $plural = $others === 1 ? 'error' : 'errors';
            $first .= " (and {$others} other {$plural})";
        }

        return $first;
    }
}
