<?php

declare(strict_types=1);

namespace Denosys\Routing;

use Denosys\Routing\Router;
use Denosys\Routing\RouteGroupInterface;

class RouteLoader
{
    /**
     * Load routes from a file into a router or route group.
     *
     * The route file will have access to a $router variable.
     *
     * @param Router|RouteGroupInterface $router The router or route group to load routes into
     * @param string $routeFile Path to the route file
     */
    public static function loadWebRoutes(Router|RouteGroupInterface $router, string $routeFile): void
    {
        if (file_exists($routeFile)) {
            // Use a closure to isolate scope and provide $router variable
            (static function (Router|RouteGroupInterface $router, string $file): void {
                require $file;
            })($router, $routeFile);
        }
    }
}
