<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Validation\ValidationException;
use Denosys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testErrorsKeepFieldKeysAndGlobalFirstMessage(): void
    {
        $validator = Validator::make([], []);
        $validator->validate();
        $validator->errors()->add('email', 'The email address is unavailable.');
        $validator->errors()->add('name', 'The name is required.');

        $exception = new ValidationException($validator);

        self::assertSame([
            'email' => ['The email address is unavailable.'],
            'name' => ['The name is required.'],
        ], $exception->getErrors());
        self::assertSame('The email address is unavailable.', $exception->getFirstError());
        self::assertSame('The name is required.', $exception->getFirstError('name'));
        self::assertSame('The email address is unavailable. (and 1 other error)', $exception->getMessage());
    }

    public function testEmptyErrorBagHasNoFirstMessage(): void
    {
        $exception = new ValidationException(Validator::make([], []));

        self::assertSame([], $exception->getErrors());
        self::assertNull($exception->getFirstError());
    }
}
