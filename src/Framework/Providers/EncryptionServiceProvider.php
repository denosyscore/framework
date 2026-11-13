<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Encryption\Encrypter;
use Denosys\Encryption\EncrypterInterface;
use Denosys\Encryption\Key;
use Denosys\Encryption\KeyRing;
use RuntimeException;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Denosys\Config\Repository;
use Psr\EventDispatcher\EventDispatcherInterface;

class EncryptionServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(Encrypter::class, fn (ContainerInterface $container): Encrypter => new Encrypter($this->keyRing($container)));

        $container->singleton(EncrypterInterface::class, function (ContainerInterface $container) {
            return $container->get(Encrypter::class);
        });

        $container->alias('encrypter', EncrypterInterface::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }

    /**
     * The ring from app.key (the current key) and app.previous_keys (a list
     * or a comma-separated string of keys that still decrypt).
     */
    private function keyRing(ContainerInterface $container): KeyRing
    {
        $config = $container->get(Repository::class);
        if (!$config instanceof Repository) {
            throw new RuntimeException('The container must provide the configuration repository.');
        }

        $current = $config->get('app.key');
        if (!is_string($current) || $current === '') {
            throw new RuntimeException('No application encryption key has been specified.');
        }

        $previous = $config->get('app.previous_keys', []);
        if (is_string($previous)) {
            $previous = explode(',', $previous);
        }
        if (!is_array($previous)) {
            throw new RuntimeException('app.previous_keys must be a list or a comma-separated string of keys.');
        }

        $keys = [];
        foreach ($previous as $key) {
            if (is_string($key) && trim($key) !== '') {
                $keys[] = Key::fromBase64(trim($key));
            }
        }

        return new KeyRing(Key::fromBase64($current), ...$keys);
    }
}
