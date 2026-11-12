<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Validation\ValidationException;
use Denosys\Validation\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DirectValidationMessagesTest extends TestCase
{
    public function testDirectMessagesPreserveFieldsAndOrdering(): void
    {
        $exception = ValidationException::withMessages([
            'email' => 'The email address is unavailable.',
            'password' => ['The password has expired.', 'Choose a new password.'],
        ]);

        self::assertSame([
            'email' => ['The email address is unavailable.'],
            'password' => ['The password has expired.', 'Choose a new password.'],
        ], $exception->validator->errors()->toArray());
        self::assertSame(
            'The email address is unavailable. (and 2 other errors)',
            $exception->getMessage(),
        );
        self::assertSame('The password has expired.', $exception->getFirstError('password'));
    }

    public function testInvalidMessageShapeFailsExplicitly(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ValidationException::withMessages(['email' => [42]]);
    }

    public function testExistingValidatorConstructorStillWorks(): void
    {
        $validator = Validator::make([], []);
        self::assertTrue($validator->validate());
        $validator->errors()->add('email', 'The email field is required.');

        $exception = new ValidationException($validator);

        self::assertSame('The email field is required.', $exception->getFirstError('email'));
    }
}
