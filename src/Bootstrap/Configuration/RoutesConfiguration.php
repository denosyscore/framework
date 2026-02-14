<?php

declare(strict_types=1);

namespace Denosys\Bootstrap\Configuration;

use Closure;
use Denosys\Routing\Router;

/**
 * Typed configuration for routes.
 * 
 * Replaces magic string 'app.routes' with a type-safe object.
 */
final class RoutesConfiguration
{
    private ?string $webPath = null;
    private ?string $apiPath = null;
    private ?Closure $customConfigurator = null;

    /**
     * Set the web routes file path.
     */
    public function web(string $path): self
    {
        $this->webPath = $path;
        return $this;
    }

    /**
     * Set the API routes file path.
     */
    public function api(string $path): self
    {
        $this->apiPath = $path;
        return $this;
    }

    /**
     * Set a custom route configurator.
     * 
     * @param Closure(Router): void $configurator
     */
    public function using(Closure $configurator): self
    {
        $this->customConfigurator = $configurator;
        return $this;
    }

    public function getWebPath(): ?string
    {
        return $this->webPath;
    }

    public function getApiPath(): ?string
    {
        return $this->apiPath;
    }

    public function getCustomConfigurator(): ?Closure
    {
        return $this->customConfigurator;
    }

    public function hasConfiguration(): bool
    {
        return $this->webPath !== null 
            || $this->apiPath !== null 
            || $this->customConfigurator !== null;
    }
}
