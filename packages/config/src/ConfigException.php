<?php

declare(strict_types=1);

namespace Denosys\Config;

use RuntimeException;

final class ConfigException extends RuntimeException
{
    public static function missing(string $path): self
    {
        return new self("Configuration [{$path}] is not set.");
    }

    public static function wrongType(string $path, string $expected, mixed $actual): self
    {
        return new self(sprintf('Configuration [%s] must be %s, %s given.', $path, $expected, get_debug_type($actual)));
    }

    public static function invalidFile(string $file, string $reason): self
    {
        return new self("Configuration file [{$file}] {$reason}.");
    }
}
