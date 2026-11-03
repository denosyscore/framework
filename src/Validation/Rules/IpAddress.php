<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

/**
 * IP address validation rule
 */
class IpAddress implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!is_string($value)) {
            return false;
        }

        $version = $parameters[0] ?? null;

        if ($version === '4') {
            return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
        }

        if ($version === '6') {
            return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
        }

        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        $version = $parameters[0] ?? null;

        if ($version === '4') {
            return 'The :attribute must be a valid IPv4 address.';
        }

        if ($version === '6') {
            return 'The :attribute must be a valid IPv6 address.';
        }

        return 'The :attribute must be a valid IP address.';
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return [];
    }

    public static function ruleName(): string
    {
        return 'ip_address';
    }
}
