<?php

declare(strict_types=1);

namespace Denosys\Routing;

use Closure;
use Denosys\Routing\RouteCollectionInterface;
use Denosys\Routing\RouteInterface;
use Denosys\Routing\Router;
use RuntimeException;

class RouteCache
{
    /**
     * Build a serializable route cache file from a route collection.
     */
    public function build(RouteCollectionInterface $routeCollection, string $cacheFile): void
    {
        $routes = [];

        foreach ($routeCollection->all() as $route) {
            $routes[] = $this->serializeRoute($route);
        }

        $cacheDirectory = dirname($cacheFile);
        if (!is_dir($cacheDirectory)) {
            mkdir($cacheDirectory, 0755, true);
        }

        $payload = [
            'generated_at' => date(DATE_ATOM),
            'routes' => $routes,
        ];

        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
        file_put_contents($cacheFile, $content, LOCK_EX);
    }

    /**
     * Load routes from cache into the given router.
     */
    public function load(Router $router, string $cacheFile): bool
    {
        if (!is_file($cacheFile)) {
            return false;
        }

        $payload = require $cacheFile;
        if (!is_array($payload) || !isset($payload['routes']) || !is_array($payload['routes'])) {
            throw new RuntimeException("Invalid route cache payload at {$cacheFile}");
        }

        foreach ($payload['routes'] as $routeData) {
            if (!is_array($routeData)) {
                throw new RuntimeException("Invalid cached route entry at {$cacheFile}");
            }

            $this->hydrateRoute($router, $routeData);
        }

        return true;
    }

    public function clear(string $cacheFile): bool
    {
        if (!is_file($cacheFile)) {
            return true;
        }

        return unlink($cacheFile);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeRoute(RouteInterface $route): array
    {
        $handler = $route->getHandler();
        $this->assertHandlerSerializable($handler, $route->getPattern());

        $middleware = $route->getMiddleware();
        $withoutMiddleware = $route->getWithoutMiddleware();

        $this->assertMiddlewareSerializable($middleware, $route->getPattern());
        $this->assertMiddlewareSerializable($withoutMiddleware, $route->getPattern());

        return [
            'methods' => $route->getMethods(),
            'pattern' => $route->getPattern(),
            'handler' => $handler,
            'name' => $route->getName(),
            'constraints' => $route->getConstraints(),
            'middleware' => $middleware,
            'without_middleware' => $withoutMiddleware,
            'host' => $route->getHost(),
            'port' => $route->getPort(),
            'scheme' => $route->getScheme(),
        ];
    }

    /**
     * @param array<string, mixed> $routeData
     */
    private function hydrateRoute(Router $router, array $routeData): void
    {
        $methods = $routeData['methods'] ?? null;
        $pattern = $routeData['pattern'] ?? null;
        $handler = $routeData['handler'] ?? null;

        if (!is_array($methods) || !is_string($pattern) || (!is_array($handler) && !is_string($handler))) {
            throw new RuntimeException('Cached route data is missing required fields.');
        }

        $route = $router->match($methods, $pattern, $handler);

        if (isset($routeData['name']) && is_string($routeData['name'])) {
            $route->name($routeData['name']);
        }

        if (isset($routeData['constraints']) && is_array($routeData['constraints'])) {
            foreach ($routeData['constraints'] as $parameter => $constraint) {
                if (is_string($parameter) && is_string($constraint)) {
                    $route->where($parameter, $constraint);
                }
            }
        }

        if (isset($routeData['middleware']) && is_array($routeData['middleware'])) {
            $route->middleware($routeData['middleware']);
        }

        if (isset($routeData['without_middleware']) && is_array($routeData['without_middleware'])) {
            $route->withoutMiddleware($routeData['without_middleware']);
        }

        if (array_key_exists('host', $routeData) && (is_string($routeData['host']) || $routeData['host'] === null)) {
            $route->setHost($routeData['host']);
        }

        if (array_key_exists('port', $routeData)) {
            $route->setPort($routeData['port']);
        }

        if (array_key_exists('scheme', $routeData) && (is_string($routeData['scheme']) || is_array($routeData['scheme']) || $routeData['scheme'] === null)) {
            $route->setScheme($routeData['scheme']);
        }
    }

    private function assertHandlerSerializable(Closure|array|string $handler, string $pattern): void
    {
        if ($handler instanceof Closure) {
            throw new RuntimeException("Cannot cache route [{$pattern}] because closure handlers are not serializable.");
        }

        if (!is_array($handler)) {
            return;
        }

        foreach ($handler as $segment) {
            if (is_object($segment)) {
                throw new RuntimeException("Cannot cache route [{$pattern}] because handler instance callables are not serializable.");
            }
        }
    }

    /**
     * @param array<int, mixed> $middleware
     */
    private function assertMiddlewareSerializable(array $middleware, string $pattern): void
    {
        foreach ($middleware as $item) {
            if (is_object($item)) {
                throw new RuntimeException("Cannot cache route [{$pattern}] because object middleware is not serializable.");
            }
        }
    }
}
