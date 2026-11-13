<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Cache\AtomicCounter;
use Denosys\Cache\FileCache;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\SimpleCache\CacheInterface;

class CacheServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(FileCache::class, function (ContainerInterface $container) {
            $config = $container->get('config');
            
            // Default to the application's storage directory, never the package's own.
            $storage = $container->has('path.storage') ? (string) $container->get('path.storage') : getcwd() . '/storage';
            $cacheDirectory = $config->get('cache.path') ?? $storage . DIRECTORY_SEPARATOR . 'cache';
            
            return new FileCache($cacheDirectory);
        });

        // One file cache is the PSR-16 cache and the atomic counter.
        $container->alias(CacheInterface::class, FileCache::class);
        $container->alias(AtomicCounter::class, FileCache::class);
        $container->alias('cache', FileCache::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }
}
