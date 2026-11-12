<?php

declare(strict_types=1);

namespace Denosys\Hashing;

use InvalidArgumentException;

/**
 * Hash manager for managing multiple hashing drivers.
 * 
 * Provides a unified interface for working with different hashing algorithms.
 */
class HashManager implements HasherInterface
{
    /**
     * The registered custom driver creators.
     *
     * @var array<string, callable>
     */
    /** @var array<string, mixed> */

    protected array $customCreators = [];

    /**
     * The registered hashers.
     *
     * @var array<string, HasherInterface>
     */
    /** @var array<string, mixed> */

    protected array $drivers = [];

    /**
     * The default driver name.
     */
    protected string $defaultDriver = 'bcrypt';

    /**
     * Create a new hash manager instance.
     *
     * @param array<string, mixed> $config
     */
    public function __construct(protected array $config = [])
    {
        $this->defaultDriver = $config['driver'] ?? 'bcrypt';
    }

    /**
     * Get a hasher instance by name.
     */
    public function driver(?string $name = null): HasherInterface
    {
        $name = $name ?? $this->defaultDriver;

        return $this->drivers[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve the given hasher.
     *
     * @throws InvalidArgumentException
     */
    protected function resolve(string $name): HasherInterface
    {
        if (isset($this->customCreators[$name])) {
            return $this->customCreators[$name]($this->config);
        }

        return match ($name) {
            'bcrypt' => new BcryptHasher($this->config['bcrypt'] ?? []),
            'argon2id' => new Argon2IdHasher($this->config['argon2id'] ?? []),
            default => throw new InvalidArgumentException("Unsupported hashing driver: {$name}"),
        };
    }

    /**
     * Register a custom driver creator.
     */
    public function extend(string $driver, callable $callback): self
    {
        $this->customCreators[$driver] = $callback;
        return $this;
    }

    /**
     * Get the default driver name.
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    /**
     * Set the default driver name.
     */
    public function setDefaultDriver(string $name): self
    {
        $this->defaultDriver = $name;
        return $this;
    }

    /**
     * Hash the given value using the default driver.
      * @param array<string, mixed> $options
     */
    public function make(string $value, array $options = []): string
    {
        return $this->driver()->make($value, $options);
    }

    /**
     * Check the given plain value against a hash.
     */
    public function check(string $value, string $hashedValue): bool
    {
        return $this->driver()->check($value, $hashedValue);
    }

    /**
     * Check if the given hash needs to be rehashed.
      * @param array<string, mixed> $options
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return $this->driver()->needsRehash($hashedValue, $options);
    }

    /**
     * Get information about the given hashed value.
     */
    /**
     * @return array<string, mixed>
     */
public function info(string $hashedValue): array
    {
        return $this->driver()->info($hashedValue);
    }
}
