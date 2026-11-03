<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Validation\Rules\After;
use Denosys\Validation\Rules\Before;
use Denosys\Validation\Rules\Date;
use PHPUnit\Framework\TestCase;

final class DateRuleDependencyTest extends TestCase
{
    public function testDateRulesWorkInAStandaloneValidationInstall(): void
    {
        self::assertTrue((new Date())->validate('date', '2026-01-01'));
        self::assertTrue((new Before())->validate('date', '2025-01-01', ['date' => '2026-01-01']));
        self::assertTrue((new After())->validate('date', '2027-01-01', ['date' => '2026-01-01']));
    }
}
