<?php

declare(strict_types=1);

use Denosys\Cache\AtomicCounter;
use Denosys\RateLimiter\RateLimiter;
use Denosys\Framework\Providers\RateLimiterServiceProvider;
use Psr\SimpleCache\CacheInterface;
use Denosys\Config\Repository;
use Denosys\Container\Container;
use Denosys\Framework\Providers\CacheServiceProvider;

it('stores cache files under the application storage path when no cache path is configured', function (): void {
    $storage = sys_get_temp_dir() . '/denosys-cache-provider-' . bin2hex(random_bytes(6));
    $container = new Container();
    $container->instance('config', new Repository());
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

it('serves one file cache as the PSR-16 cache, the atomic counter and the rate limiter store', function (): void {
    $container = new Container();
    $container->instance('config', new Repository(['cache' => ['path' => sys_get_temp_dir() . '/denosys-cache-provider-' . bin2hex(random_bytes(6))]]));

    (new CacheServiceProvider())->register($container);
    (new RateLimiterServiceProvider())->register($container);

    expect($container->get(AtomicCounter::class))->toBe($container->get(CacheInterface::class))
        ->and($container->get(RateLimiter::class))->toBeInstanceOf(RateLimiter::class);
});
