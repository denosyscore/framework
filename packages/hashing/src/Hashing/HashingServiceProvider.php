<?php

declare(strict_types=1);

namespace Denosys\Hashing;

use Denosys\Container\ContainerInterface;
use Denosys\Contracts\ServiceProviderInterface;
use Denosys\Config\ConfigurationInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class HashingServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(HashManager::class, function (ContainerInterface $container) {
            $config = $container->get(ConfigurationInterface::class);
            
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
