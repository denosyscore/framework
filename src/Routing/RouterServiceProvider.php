<?php

declare(strict_types=1);

namespace Denosys\Routing;

use Denosys\Container\ContainerInterface;
use Denosys\Contracts\ServiceProviderInterface;
use Denosys\Bootstrap\Configuration\MiddlewareConfiguration;
use Denosys\Bootstrap\Configuration\RoutesConfiguration;
use Denosys\Routing\Router;
use Denosys\Routing\UrlGenerator;
use Denosys\Routing\MiddlewareRegistry;
use Denosys\Container\Exceptions\NotFoundException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Denosys\Routing\UrlGeneratorInterface;
use Denosys\Container\Exceptions\ContainerResolutionException;
use Throwable;

class RouterServiceProvider implements ServiceProviderInterface
{
    /**
     * @param array<string, mixed> $globalMiddleware
     * @param array<string, mixed> $webMiddleware
     * @param array<string, mixed> $apiMiddleware
      * @var array<class-string>
     */
    protected array $globalMiddleware = [
        \Denosys\Http\Middleware\CorsMiddleware::class,
    ];

    /**
     * @param array<string, mixed> $webMiddleware
     * @param array<string, mixed> $apiMiddleware
      * @var array<class-string>
     */
    protected array $webMiddleware = [
        \Denosys\Session\StartSessionMiddleware::class,
        \Denosys\Http\Middleware\VerifyCsrfToken::class,
        \Denosys\Http\Middleware\RateLimitExceptionMiddleware::class,
        \Denosys\Http\Middleware\ValidationExceptionMiddleware::class,
    ];

    /** @var array<string, mixed> */


    protected array $apiMiddleware = [];

    /**
     * @throws NotFoundException
     * @throws ContainerResolutionException
     */
    public function register(ContainerInterface $container): void
    {
        $this->registerMiddlewareRegistry($container);
        $this->registerRouter($container);
        $this->registerUrlGenerator($container);
    }

    /**
     * @throws ContainerResolutionException
     */
    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        $router = $container->get(Router::class);
        $registry = $container->get(MiddlewareRegistry::class);

        $this->applyMiddlewareConfiguration($container, $registry);
        $this->applyRoutesConfiguration($container, $router, $registry);
    }

    /**
     * @throws NotFoundException
     */
    private function registerMiddlewareRegistry(ContainerInterface $container): void
    {
        $container->singleton(MiddlewareRegistry::class, function () use ($container) {
            $registry = new MiddlewareRegistry();
            $registry->group('web', $this->webMiddleware);
            $registry->group('api', $this->apiMiddleware);
            $registry->aliases($this->middlewareAliases($container));
            return $registry;
        });

        $container->alias('middleware.registry', MiddlewareRegistry::class);
    }

    /** @return array<string, class-string> */
    /**
     * @return array<string, mixed>
     */
protected function middlewareAliases(ContainerInterface $container): array
    {
        $defaults = [
            'throttle.login' => \Denosys\RateLimiter\Middleware\ThrottleLoginMiddleware::class,
            'throttle.register' => \Denosys\RateLimiter\Middleware\ThrottleRegisterMiddleware::class,
            'throttle.password_reset' => \Denosys\RateLimiter\Middleware\ThrottlePasswordResetMiddleware::class,
            'auth' => \Denosys\Auth\Middleware\AuthenticateMiddleware::class,
            'guest' => \Denosys\Auth\Middleware\GuestMiddleware::class,
        ];

        if (!$container->has('config')) {
            return $defaults;
        }

        try {
            $config = $container->get('config');
            $customAliases = $config->get('routing.middleware_aliases', []);

            if (!is_array($customAliases)) {
                return $defaults;
            }

            return array_merge($defaults, $customAliases);
        } catch (Throwable) {
            return $defaults;
        }
    }

    /**
     * @throws ContainerResolutionException
     */
    private function applyMiddlewareConfiguration(ContainerInterface $container, MiddlewareRegistry $registry): void
    {
        if (!$container->has(MiddlewareConfiguration::class)) {
            return;
        }

        $config = $container->get(MiddlewareConfiguration::class);
        $config->apply($registry);
    }

    /**
     * @throws ContainerResolutionException
     */
    private function applyRoutesConfiguration(ContainerInterface $container, Router $router, MiddlewareRegistry $registry): void
    {
        $basePath = $container->get('path.base');
        $routeCache = new RouteCache();
        $routeCacheFile = $this->resolveRouteCacheFile($container, $basePath);

        if ($routeCache->load($router, $routeCacheFile)) {
            return;
        }

        $config = $this->resolveRoutesConfiguration($container);
        if ($config !== null) {
            if ($config->getWebPath() !== null) {
                $webPath = $this->resolvePath($config->getWebPath(), $basePath);
                $this->loadRoutesWithMiddleware($router, $registry, 'web', $webPath);
            }

            if ($config->getApiPath() !== null) {
                $apiPath = $this->resolvePath($config->getApiPath(), $basePath);
                $this->loadRoutesWithMiddleware($router, $registry, 'api', $apiPath, '/api');
            }

            if ($config->getCustomConfigurator() !== null) {
                ($config->getCustomConfigurator())($router);
            }
            
            return;
        }

        // Default: load routes/web.php
        $webPath = $basePath . DIRECTORY_SEPARATOR . 'routes/web.php';
        $this->loadRoutesWithMiddleware($router, $registry, 'web', $webPath);
    }

    private function resolveRoutesConfiguration(ContainerInterface $container): ?RoutesConfiguration
    {
        try {
            /** @var RoutesConfiguration $config */
            $config = $container->get(RoutesConfiguration::class);
        } catch (Throwable) {
            return null;
        }

        if (!$config->hasConfiguration()) {
            return null;
        }

        return $config;
    }

    private function resolveRouteCacheFile(ContainerInterface $container, string $basePath): string
    {
        try {
            if ($container->has('app')) {
                $app = $container->get('app');
                if (is_object($app) && method_exists($app, 'routeCacheFile')) {
                    /** @var string $routeCacheFile */
                    $routeCacheFile = $app->routeCacheFile();
                    return $routeCacheFile;
                }
            }
        } catch (Throwable) {
            // Fall through to default.
        }

        return $basePath . DIRECTORY_SEPARATOR . 'storage/core/cache/routes.php';
    }

    private function loadRoutesWithMiddleware(Router $router, MiddlewareRegistry $registry, string $group, string $path, string $prefix = ''): void
    {
        $middleware = $registry->resolve($group);

        if (!empty($middleware) || !empty($prefix)) {
            $router->middleware($middleware)->group($prefix, fn($g) => RouteLoader::loadWebRoutes($g, $path));
        } else {
            RouteLoader::loadWebRoutes($router, $path);
        }
    }

    private function resolvePath(string $path, string $basePath): string
    {
        return str_starts_with($path, '/') ? $path : $basePath . DIRECTORY_SEPARATOR . $path;
    }

    /**
     * @throws NotFoundException
     * @throws ContainerResolutionException
     */
    private function registerRouter(ContainerInterface $container): void
    {
        $container->singleton(Router::class, function (ContainerInterface $container) {
            $registry = $container->get(MiddlewareRegistry::class);
            
            // Create custom invocation strategy with route model binding support
            $invocationStrategy = new \Denosys\Routing\Strategy\ModelBindingInvocationStrategy($container);
            
            // Create dispatcher with our custom strategy
            $routeCollection = new \Denosys\Routing\RouteCollection();
            $routeManager = new \Denosys\Routing\RouteManager();
            
            $dispatcher = \Denosys\Routing\Dispatcher::withDefaults(
                routeCollection: $routeCollection,
                routeManager: $routeManager,
                container: $container,
                invocationStrategy: $invocationStrategy,
                middlewareRegistry: $registry
            );
            
            $router = new Router(
                container: $container,
                routeCollection: $routeCollection,
                routeManager: $routeManager,
                dispatcher: $dispatcher,
                middlewareRegistry: $registry
            );
            
            $router->use($this->globalMiddleware);
            
            return $router;
        });

        $container->alias('router', Router::class);
    }

    /**
     * @throws NotFoundException
     * @throws ContainerResolutionException
     */
    private function registerUrlGenerator(ContainerInterface $container): void
    {
        $container->singleton(UrlGeneratorInterface::class, function (ContainerInterface $container) {
            $routes = $container->get('router')->getRouteCollection();
            $container->instance('routes', $routes);
            return new UrlGenerator($routes);
        });

        $container->alias('url', UrlGeneratorInterface::class);

        $container->extend('url', function (UrlGeneratorInterface $url, ContainerInterface $container) {
            // Set request if available (may not exist in queue worker context)
            if ($container->has('request')) {
                $url->setRequest($container->get('request'));
            }
            
            // Always set key resolver - this is critical for signed URLs
            $url->setKeyResolver(fn() => $container->get('config')->get('app.key'));
            $url->setBaseUrl($container->get('config')->get('app.url', ''));
            $url->setAssetUrl($container->get('config')->get('app.asset_url', ''));
            return $url;
        });
    }
}
