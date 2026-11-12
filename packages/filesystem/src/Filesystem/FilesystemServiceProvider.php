<?php

declare(strict_types=1);

namespace Denosys\Filesystem;

use Denosys\Container\ContainerInterface;
use Denosys\Contracts\Config\ConfigurationReaderInterface;
use Denosys\Contracts\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Filesystem service provider.
 */
class FilesystemServiceProvider implements ServiceProviderInterface
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
                $container->get(ConfigurationReaderInterface::class),
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
