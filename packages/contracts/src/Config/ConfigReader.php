<?php

declare(strict_types=1);

namespace Denosys\Contracts\Config;

/**
 * Read a named configuration value without depending on a configuration loader.
 */
interface ConfigReader
{
    public function get(string $key, mixed $default = null): mixed;
}
