<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Cache\CacheInterface;
use Denosys\Cache\FileCache;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;

class CacheServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(CacheInterface::class, function (ContainerInterface $container) {
            $config = $container->get('config');
            
            // Default to the application's storage directory, never the package's own.
            $storage = $container->has('path.storage') ? (string) $container->get('path.storage') : getcwd() . '/storage';
            $cacheDirectory = $config->get('cache.path') ?? $storage . DIRECTORY_SEPARATOR . 'cache';
            
            return new FileCache($cacheDirectory);
        });

        $container->alias('cache', CacheInterface::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }
}
