<?php

declare(strict_types=1);

namespace Denosys\Bootstrap\Configuration;

use Closure;
use Denosys\Http\Exceptions\ExceptionHandler;

/**
 * Typed configuration for exception handling.
 * 
 * Replaces magic string 'app.exceptions' with a type-safe object.
 */
final class ExceptionConfiguration
{
    /** @var Closure|null */
    private ?Closure $configurator = null;

    /**
     * Set a closure that configures the ExceptionHandler.
     * 
     * @param Closure(ExceptionHandler): void $configurator
     */
    public function using(Closure $configurator): self
    {
        $this->configurator = $configurator;
        return $this;
    }

    /**
     * Apply the configuration to an ExceptionHandler.
     */
    public function apply(ExceptionHandler $handler): void
    {
        if ($this->configurator !== null) {
            ($this->configurator)($handler);
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
