<?php

declare(strict_types=1);

use Denosys\Config\Repository;
use Denosys\Container\Container;
use Denosys\Encryption\Encrypter;
use Denosys\Encryption\EncrypterInterface;
use Denosys\Encryption\Key;
use Denosys\Encryption\KeyRing;
use Denosys\Framework\Providers\EncryptionServiceProvider;

it('builds the key ring from app.key and app.previous_keys', function (): void {
    $old = Encrypter::generateKey();
    $payload = (new Encrypter(new KeyRing(Key::fromBase64($old))))->encrypt('kept');
    $container = new Container();
    $config = new Repository(['app' => ['key' => Encrypter::generateKey(), 'previous_keys' => ' ' . $old . ' ,']]);
    $container->instance(Repository::class, $config);
    $container->instance('config', $config);

    (new EncryptionServiceProvider())->register($container);
    $encrypter = $container->get(EncrypterInterface::class);

    expect($encrypter->decrypt($payload))->toBe('kept')
        ->and($encrypter->needsReEncryption($payload))->toBeTrue();
});

it('refuses to start without app.key', function (): void {
    $container = new Container();
    $config = new Repository(['app' => ['key' => '']]);
    $container->instance(Repository::class, $config);

    (new EncryptionServiceProvider())->register($container);
    $container->get(EncrypterInterface::class);
})->throws(Denosys\Container\Exceptions\ContainerResolutionException::class, 'No application encryption key has been specified.');
