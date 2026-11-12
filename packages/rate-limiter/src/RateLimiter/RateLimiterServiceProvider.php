<?php

declare(strict_types=1);

namespace Denosys\RateLimiter;

use Denosys\Cache\CacheInterface;
use Denosys\Container\ContainerInterface;
use Denosys\Http\Middleware\ThrottleRequestsMiddleware;
use Denosys\RateLimiter\Middleware\ThrottleLoginMiddleware;
use Denosys\RateLimiter\Middleware\ThrottleRegisterMiddleware;
use Denosys\RateLimiter\Middleware\ThrottlePasswordResetMiddleware;
use Denosys\Contracts\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

class RateLimiterServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(RateLimiter::class, function (ContainerInterface $container) {
            return new RateLimiter(
                $container->get(CacheInterface::class)
            );
        });

        // Register default throttle middleware (login limits)
        $container->singleton(ThrottleRequestsMiddleware::class, function (ContainerInterface $container) {
            $config = $container->get('config');
            
            return new ThrottleRequestsMiddleware(
                $container->get(RateLimiter::class),
                $config->get('auth.rate_limiting.login.max_attempts', 5),
                $config->get('auth.rate_limiting.login.decay_minutes', 1),
                'login'
            );
        });

        // Register login-specific throttle middleware
        $container->singleton(ThrottleLoginMiddleware::class, function (ContainerInterface $container) {
            $config = $container->get('config');
            
            return new ThrottleLoginMiddleware(
                $container->get(RateLimiter::class),
                $config->get('auth.rate_limiting.login.max_attempts', 5),
                $config->get('auth.rate_limiting.login.decay_minutes', 1),
            );
        });

        // Register register-specific throttle middleware
        $container->singleton(ThrottleRegisterMiddleware::class, function (ContainerInterface $container) {
            $config = $container->get('config');
            
            return new ThrottleRegisterMiddleware(
                $container->get(RateLimiter::class),
                $config->get('auth.rate_limiting.register.max_attempts', 3),
                $config->get('auth.rate_limiting.register.decay_minutes', 1),
            );
        });

        // Register password reset throttle middleware
        $container->singleton(ThrottlePasswordResetMiddleware::class, function (ContainerInterface $container) {
            $config = $container->get('config');
            
            return new ThrottlePasswordResetMiddleware(
                $container->get(RateLimiter::class),
                $config->get('auth.rate_limiting.password_reset.max_attempts', 3),
                $config->get('auth.rate_limiting.password_reset.decay_minutes', 1),
            );
        });

        $container->alias('rate_limiter', RateLimiter::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }
}
