<?php

declare(strict_types=1);

namespace Denosys\Validation\Rules;

use SplFileInfo;

class Dimensions implements RuleInterface
{
    /**
     * @param array<int|string, mixed> $parameters
     * @param array<string, mixed> $data
     */
    public function validate(string $field, mixed $value, array $parameters = [], array $data = []): bool
    {
        if (!$value instanceof SplFileInfo) {
            return false;
        }

        $imageInfo = @getimagesize($value->getPathname());

        if ($imageInfo === false) {
            return false;
        }

        [$width, $height] = $imageInfo;

        $constraints = $this->parseParameters($parameters);

        if (isset($constraints['width']) && $width != $constraints['width']) {
            return false;
        }

        if (isset($constraints['height']) && $height != $constraints['height']) {
            return false;
        }

        if (isset($constraints['min_width']) && $width < $constraints['min_width']) {
            return false;
        }

        if (isset($constraints['max_width']) && $width > $constraints['max_width']) {
            return false;
        }

        if (isset($constraints['min_height']) && $height < $constraints['min_height']) {
            return false;
        }

        if (isset($constraints['max_height']) && $height > $constraints['max_height']) {
            return false;
        }

        if (isset($constraints['ratio'])) {
            $ratio = $width / $height;
            $expectedRatio = $this->parseRatio($constraints['ratio']);

            if (abs($ratio - $expectedRatio) > 0.01) {
                return false;
            }
        }

        return true;
    }

    /**

     * @return array<string, mixed>

      * @param array<int, mixed> $parameters
     */

private function parseParameters(array $parameters): array

    {
        $constraints = [];

        foreach ($parameters as $parameter) {
            if (str_contains($parameter, '=')) {
                [$key, $value] = explode('=', $parameter, 2);
                $constraints[$key] = $value;
            }
        }

        return $constraints;
    }

    private function parseRatio(string $ratio): float
    {
        if (str_contains($ratio, '/')) {
            [$width, $height] = explode('/', $ratio);

            return (float) $width / (float) $height;
        }

        return (float) $ratio;
    }

    /**
     * @param array<int|string, mixed> $parameters
     */
    public function message(string $field, array $parameters = []): string
    {
        return 'The :attribute has invalid image dimensions.';
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
        return 'dimensions';
    }
}
