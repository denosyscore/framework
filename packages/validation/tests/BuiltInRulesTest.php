<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests;

use Denosys\Validation\Rules\RuleInterface;
use Denosys\Validation\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** The validator must work on its own, without a service provider registering rules. */
final class BuiltInRulesTest extends TestCase
{
    protected function setUp(): void
    {
        Validator::reset();
    }

    protected function tearDown(): void
    {
        Validator::reset();
    }

    public function testBuiltInRulesWorkWithoutRegistration(): void
    {
        $invalid = Validator::make(['email' => 'x'], ['email' => 'required|email', 'name' => 'required']);
        self::assertTrue($invalid->fails());
        self::assertTrue($invalid->errors()->has('email'));
        self::assertTrue($invalid->errors()->has('name'));

        $valid = Validator::make(
            ['email' => 'ada@example.com', 'name' => 'Ada', 'age' => '36'],
            ['email' => 'required|email', 'name' => 'required|string|max:50', 'age' => 'nullable|integer|min:18'],
        );
        self::assertTrue($valid->passes());
        self::assertSame(['email' => 'ada@example.com', 'name' => 'Ada', 'age' => '36'], $valid->validated());
    }

    /** @return iterable<string, array{class-string<RuleInterface>}> */
    public static function ruleClasses(): iterable
    {
        foreach (glob(dirname(__DIR__) . '/src/Validation/Rules/*.php') ?: [] as $file) {
            $class = 'Denosys\\Validation\\Rules\\' . basename($file, '.php');
            if ((new \ReflectionClass($class))->implementsInterface(RuleInterface::class) && !(new \ReflectionClass($class))->isInterface()) {
                yield $class::ruleName() => [$class];
            }
        }
    }

    /** Every rule the package ships is available by name. */
    #[DataProvider('ruleClasses')]
    public function testEveryShippedRuleIsAvailableByName(string $class): void
    {
        self::assertTrue(Validator::hasRule($class::ruleName()), "Rule [{$class::ruleName()}] is not available by default.");
    }

    public function testApplicationRulesOverrideBuiltInRules(): void
    {
        Validator::extend('email', AlwaysPasses::class);

        self::assertTrue(Validator::make(['email' => 'not an email'], ['email' => 'email'])->passes());
    }
}

final class AlwaysPasses implements RuleInterface
{
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        return true;
    }

    public function message(string $field, array $parameters = []): string
    {
        return '';
    }

    public static function parameterNames(): array
    {
        return [];
    }

    public static function ruleName(): string
    {
        return 'always_passes';
    }
}
