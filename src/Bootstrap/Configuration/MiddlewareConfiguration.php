<?php

declare(strict_types=1);

namespace Denosys\Bootstrap\Configuration;

use Closure;
use Denosys\Routing\MiddlewareRegistry;

/**
 * Typed configuration for middleware.
 * 
 * Replaces magic string 'app.middleware' with a type-safe object.
 */
final class MiddlewareConfiguration
{
    /** @var Closure|null */
    private ?Closure $configurator = null;

    /**
     * Set a closure that configures the MiddlewareRegistry.
     * 
     * @param Closure(MiddlewareRegistry): void $configurator
     */
    public function using(Closure $configurator): self
    {
        $this->configurator = $configurator;
        return $this;
    }

    /**
     * Apply the configuration to a MiddlewareRegistry.
     */
    public function apply(MiddlewareRegistry $registry): void
    {
        if ($this->configurator !== null) {
            ($this->configurator)($registry);
        }
    }

    /**
     * Check if configuration is set.
     */
    public function hasConfiguration(): bool
    {
        return $this->configurator !== null;
    }
}
