<?php

declare(strict_types=1);

namespace Denosys\Config;

/**
 * A typed group of settings, built once from the repository.
 *
 * Reading settings into a section moves type and presence errors to boot time
 * and gives consumers properties instead of string paths and casts.
 */
interface ConfigSection
{
    public static function from(Repository $config): static;
}
