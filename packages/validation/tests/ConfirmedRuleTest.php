<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Validation\Rules\Confirmed;
use Denosys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class ConfirmedRuleTest extends TestCase
{
    protected function setUp(): void
    {
        Validator::extend('confirmed', Confirmed::class);
    }

    protected function tearDown(): void
    {
        Validator::reset();
    }

    public function testMissingDefaultOrCustomConfirmationProducesAFieldError(): void
    {
        foreach (['confirmed', 'confirmed:repeat_password'] as $rule) {
            $validator = Validator::make(
                ['password' => 'example-secret'],
                ['password' => $rule],
            );

            self::assertFalse($validator->validate());
            self::assertTrue($validator->errors()->has('password'));
        }
    }

    public function testMatchingDefaultAndCustomConfirmationsStillPass(): void
    {
        self::assertTrue(Validator::make(
            ['password' => 'example-secret', 'password_confirmation' => 'example-secret'],
            ['password' => 'confirmed'],
        )->validate());

        self::assertTrue(Validator::make(
            ['password' => 'example-secret', 'repeat_password' => 'example-secret'],
            ['password' => 'confirmed:repeat_password'],
        )->validate());
    }
}
