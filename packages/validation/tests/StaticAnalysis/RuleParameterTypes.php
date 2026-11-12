<?php

declare(strict_types=1);

namespace Denosys\Validation\Tests\StaticAnalysis;

use Denosys\Validation\Rules\In;
use Denosys\Validation\Rules\Min;
use Denosys\Validation\Rules\RuleInterface;
use Denosys\Validation\Rules\Unique;

final class RuleParameterTypes
{
    public function uniqueWithNamedIntegerId(Unique $rule): bool
    {
        return $rule->validate('email', 'member@example.test', [
            'table' => 'accounts',
            'column' => 'email',
            'ignore_id' => 12,
        ]);
    }

    public function minimumWithNamedInteger(Min $rule): bool
    {
        return $rule->validate('age', 18, ['min' => 16]);
    }

    public function valuesWithNamedList(In $rule): bool
    {
        return $rule->validate('tier', 'premium', ['values' => ['standard', 'premium']]);
    }

    public function interfaceAcceptsBinderShape(RuleInterface $rule): bool
    {
        return $rule->validate('field', 'value', ['optional' => null]);
    }

    public function messageAcceptsNamedValues(RuleInterface $rule): string
    {
        return $rule->message('field', ['values' => ['standard', 'premium']]);
    }
}
