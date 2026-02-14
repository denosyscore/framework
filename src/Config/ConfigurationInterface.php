<?php

declare(strict_types=1);

namespace Denosys\Config;

interface ConfigurationInterface
{
    /**
     * Retrieves the value associated with the given key
     * or returns the default value if it is set.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Check if a configuration value exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Retrieves all configuration value from the configuration file.
     *
     * @return array<string, mixed> */
    public function all(): array;

    public function loadFiles(): void;
}
