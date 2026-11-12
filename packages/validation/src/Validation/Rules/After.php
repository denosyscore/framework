<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use Throwable;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

class After implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!array_key_exists('date', $parameters)) {
            throw new InvalidArgumentException('After rule requires a date parameter.');
        }

        $param = $parameters['date'];
        $isFieldRef = is_string($param) && array_key_exists($param, $data);
        $afterInput = $isFieldRef ? $data[$param] : $param;

        $after = $this->toCarbon($afterInput);
        if ($after === null) {
            if (!$isFieldRef) {
                $dateToday = CarbonImmutable::now()->format('Y-m-d');
                throw new InvalidArgumentException(
                    "After rule \"date\" parameter must be a valid date string (e.g., \"$dateToday\", \"tomorrow\", \"next week\"), " .
                    "a UNIX timestamp, a DateTime instance, or a field name containing a valid date."
                );
            }

            return false;
        }

        $date = $this->toCarbon($value);
        if ($date === null) {
            return false;
        }

        return $date->greaterThan($after);
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute must be a date after :date.';
    }

    /**

     * @return array<int, string>

     */

public static function parameterNames(): array

    {
        return ['date'];
    }

    public static function ruleName(): string
    {
        return 'after';
    }

    private function toCarbon(mixed $input): ?CarbonImmutable
    {
        if ($input instanceof \DateTimeInterface) {
            return CarbonImmutable::instance($input);
        }

        if (is_int($input) || (is_string($input) && preg_match('/^\d+$/', $input))) {
            try {
                return CarbonImmutable::createFromTimestamp((int) $input);
            } catch (Throwable) {
                return null;
            }
        }

        if ($input === null || (is_string($input) && trim($input) === '')) {
            return null;
        }

        try {
            return CarbonImmutable::make($input);
        } catch (Throwable) {
            return null;
        }
    }
}
