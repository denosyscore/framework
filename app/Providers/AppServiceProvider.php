<?php

declare(strict_types=1);

namespace App\Providers;

use CFXP\Core\Container\ContainerInterface;
use CFXP\Core\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class AppServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        // Register app bindings.
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        // Boot app services.
    }
}
