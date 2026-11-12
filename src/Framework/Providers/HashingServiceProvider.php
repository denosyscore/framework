<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Hashing\HashManager;
use Denosys\Hashing\HasherInterface;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Denosys\Config\Repository;
use Psr\EventDispatcher\EventDispatcherInterface;

class HashingServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(HashManager::class, function (ContainerInterface $container) {
            $config = $container->get(Repository::class);
            
            return new HashManager($config->get('hashing', [
                'driver' => 'bcrypt',
                'bcrypt' => ['rounds' => 12],
                'argon2id' => [
                    'memory' => 65536,
                    'time' => 4,
                    'threads' => 1,
                ],
            ]));
        });

        $container->singleton(HasherInterface::class, function (ContainerInterface $container) {
            return $container->get(HashManager::class);
        });

        $container->alias('hash', HasherInterface::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }
}
