<?php

declare(strict_types=1);

use Denosys\Cache\CacheInterface;
use Denosys\Config\Configuration;
use Denosys\Container\Container;
use Denosys\Framework\Providers\CacheServiceProvider;

it('stores cache files under the application storage path when no cache path is configured', function (): void {
    $storage = sys_get_temp_dir() . '/denosys-cache-provider-' . bin2hex(random_bytes(6));
    $container = new Container();
    $container->instance('config', new Configuration());
    $container->instance('path.storage', $storage);

    (new CacheServiceProvider())->register($container);
    $container->get(CacheInterface::class)->set('probe', 'value');

    try {
        expect(glob($storage . '/cache/*'))->not->toBeEmpty();
    } finally {
        $container->get(CacheInterface::class)->clear();
        foreach (array_reverse(glob($storage . '/{,*/}*', GLOB_BRACE) ?: []) as $path) {
            is_dir($path) ? rmdir($path) : unlink($path);
        }
        @rmdir($storage);
    }
});
