<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Validation\Rules\Regex;
use Denosys\Validation\Validator;
use PHPUnit\Framework\TestCase;

final class RegexRuleTest extends TestCase
{
    protected function setUp(): void
    {
        Validator::extend('regex', Regex::class);
    }

    protected function tearDown(): void
    {
        Validator::reset();
    }

    public function testParameterizedPatternReachesTheRule(): void
    {
        self::assertTrue(Validator::make(
            ['date' => '2026-10-07'],
            ['date' => 'regex:/^\\d{4}-\\d{2}-\\d{2}$/'],
        )->validate());

        $invalid = Validator::make(
            ['date' => 'not-a-date'],
            ['date' => 'regex:/^\\d{4}-\\d{2}-\\d{2}$/'],
        );

        self::assertFalse($invalid->validate());
        self::assertTrue($invalid->errors()->has('date'));
    }

    public function testDirectPositionalPatternRemainsSupported(): void
    {
        self::assertTrue((new Regex())->validate('code', 'ABC-123', ['/^ABC-\\d{3}$/']));
    }
}
