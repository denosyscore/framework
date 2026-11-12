<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;

class SecurityHeadersServiceProvider implements Provider
{
    public function register(ContainerInterface $container): void
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('X-XSS-Protection: 1; mode=block');

            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
            }
        }
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {}
}
