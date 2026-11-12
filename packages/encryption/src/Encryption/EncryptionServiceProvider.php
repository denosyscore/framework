<?php

declare(strict_types=1);

namespace Denosys\Encryption;

use RuntimeException;
use Denosys\Container\ContainerInterface;
use Denosys\Contracts\ServiceProviderInterface;
use Denosys\Config\ConfigurationInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class EncryptionServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(Encrypter::class, function (ContainerInterface $container) {
            $key = $this->getEncryptionKey($container);

            return new Encrypter($key);
        });

        $container->singleton(EncrypterInterface::class, function (ContainerInterface $container) {
            return $container->get(Encrypter::class);
        });

        $container->alias('encrypter', EncrypterInterface::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }

    protected function getEncryptionKey(ContainerInterface $container): string
    {
        /** @var ConfigurationInterface $config */
        $config = $container->get(ConfigurationInterface::class);

        $key = $config->get('app.key');

        if (null === $key || '' === $key) {
            throw new RuntimeException('No application encryption key has been specified.');
        }

        return $key;
    }
}
