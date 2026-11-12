<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Filesystem\Filesystem;
use Denosys\Filesystem\FilesystemInterface;
use Denosys\Filesystem\FilesystemManager;
use Denosys\Container\ContainerInterface;
use Denosys\Contracts\Config\ConfigReader;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Filesystem service provider.
 */
class FilesystemServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(FilesystemManager::class, function (ContainerInterface $container) {
            $root = null;
            if ($container->has('path.storage')) {
                $storagePath = $container->get('path.storage');
                if (!is_string($storagePath) || $storagePath === '') {
                    throw new \InvalidArgumentException('path.storage must be a non-empty string');
                }
                $root = rtrim($storagePath, '/') . '/app';
            }

            return new FilesystemManager(
                $container->get(ConfigReader::class),
                $root
            );
        });

        // Alias for convenience
        $container->alias('filesystem', FilesystemManager::class);

        // Bind the default disk as FilesystemInterface
        $container->singleton(FilesystemInterface::class, function (ContainerInterface $container) {
            return $container->get(FilesystemManager::class)->disk();
        });
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }
}
