<?php

declare(strict_types=1);

namespace Denosys\Config;

use Denosys\Contracts\Config\ConfigReader;

/**
 * Configuration values read by dot path.
 *
 * A stored null is a value: get() returns it instead of the default, and has()
 * reports it as present. The default is returned only when the path is absent
 * and is never indexed into.
 */
final class Repository implements ConfigReader
{
    /**
     * @param array<string, mixed> $items
     */
    public function __construct(private array $items = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        [$found, $value] = $this->lookup($key);

        return $found ? $value : $default;
    }

    public function has(string $key): bool
    {
        return $this->lookup($key)[0];
    }

    /**
     * Set a value by dot path, creating intermediate arrays.
     */
    public function set(string $key, mixed $value): void
    {
        $node = &$this->items;
        foreach (explode('.', $key) as $segment) {
            if (!isset($node[$segment]) || !is_array($node[$segment])) {
                $node[$segment] = [];
            }
            $node = &$node[$segment];
        }
        $node = $value;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->items;
    }

    public function string(string $key, ?string $default = null): string
    {
        $value = $this->required($key, $default);
        if (!is_string($value)) {
            throw ConfigException::wrongType($key, 'a string', $value);
        }

        return $value;
    }

    public function int(string $key, ?int $default = null): int
    {
        $value = $this->required($key, $default);
        if (!is_int($value)) {
            throw ConfigException::wrongType($key, 'an integer', $value);
        }

        return $value;
    }

    public function bool(string $key, ?bool $default = null): bool
    {
        $value = $this->required($key, $default);
        if (!is_bool($value)) {
            throw ConfigException::wrongType($key, 'a boolean', $value);
        }

        return $value;
    }

    /**
     * @param array<mixed>|null $default
     * @return array<mixed>
     */
    public function array(string $key, ?array $default = null): array
    {
        $value = $this->required($key, $default);
        if (!is_array($value)) {
            throw ConfigException::wrongType($key, 'an array', $value);
        }

        return $value;
    }

    /**
     * Build a typed section from this repository.
     *
     * @template T of ConfigSection
     * @param class-string<T> $section
     * @return T
     */
    public function section(string $section): ConfigSection
    {
        return $section::from($this);
    }

    /**
     * The value at a path, the default when the path is absent, or an error.
     */
    private function required(string $key, mixed $default): mixed
    {
        [$found, $value] = $this->lookup($key);
        if ($found) {
            return $value;
        }
        if ($default !== null) {
            return $default;
        }

        throw ConfigException::missing($key);
    }

    /**
     * @return array{bool, mixed}
     */
    private function lookup(string $key): array
    {
        $node = $this->items;
        foreach (explode('.', $key) as $segment) {
            if (is_array($node) && array_key_exists($segment, $node)) {
                $node = $node[$segment];
            } elseif ($node instanceof \ArrayAccess && $node->offsetExists($segment)) {
                $node = $node[$segment];
            } else {
                return [false, null];
            }
        }

        return [true, $node];
    }
}
