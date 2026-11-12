<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Config\Repository;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

require_once __DIR__ . '/validation.php';

if (!function_exists('container')) {
    /**
     * Get the global container instance.
     *
     * @param  string|null  $abstract
     *
     * @return mixed
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function container(?string $abstract = null): mixed
    {
        $container = Application::getContainer();

        return $abstract ? $container->get($abstract) : $container;
    }
}

if (!function_exists('app')) {
    /**
     * Get the Application instance
     *
     * @return Application
     */
    function app(): Application
    {
        return Application::getInstance();
    }
}

if (!function_exists('request')) {
    /**
     * Get the current request instance.
     *
     * @return \Psr\Http\Message\ServerRequestInterface|null
     */
    function request(): ?\Psr\Http\Message\ServerRequestInterface
    {
        try {
            return container('request');
        } catch (Throwable) {
            return null;
        }
    }
}

if (!function_exists('route_is')) {
    /**
     * Check if the current request matches a named route.
     *
     * @param  string  $routeName  The route name to check against
     * @return bool
     */
    function route_is(string $routeName): bool
    {
        $request = request();
        if (!$request) {
            return false;
        }

        try {
            /** @var \Denosys\Routing\UrlGeneratorInterface $url */
            $url = container('url');
            $routeUrl = $url->route($routeName);
            $currentPath = $request->getUri()->getPath();
            
            // Extract path from generated URL (may include domain)
            $routePath = parse_url($routeUrl, PHP_URL_PATH) ?? $routeUrl;
            
            return rtrim($currentPath, '/') === rtrim($routePath, '/');
        } catch (Throwable) {
            return false;
        }
    }
}

if (!function_exists('route_contains')) {
    /**
     * Check if the current request path starts with a named route's path.
     * Useful for parent menu items that should be active for child routes.
     *
     * @param  string  $routeName  The route name to check against
     * @return bool
     */
    function route_contains(string $routeName): bool
    {
        $request = request();
        if (!$request) {
            return false;
        }

        try {
            /** @var \Denosys\Routing\UrlGeneratorInterface $url */
            $url = container('url');
            $routeUrl = $url->route($routeName);
            $currentPath = $request->getUri()->getPath();
            
            // Extract path from generated URL (may include domain)
            $routePath = parse_url($routeUrl, PHP_URL_PATH) ?? $routeUrl;
            
            return str_starts_with(rtrim($currentPath, '/'), rtrim($routePath, '/'));
        } catch (Throwable) {
            return false;
        }
    }
}

if (!function_exists('config')) {
    /**
     * Get application configuration values.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     *
     * @return mixed
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        /** @var Repository $config */
        $config = container(Repository::class);

        if ($key === null) {
            return $config;
        }

        return $config->get($key, $default);
    }
}

if (!function_exists('url')) {
    /**
     * Build an absolute URL from the base app URL and a relative path.
     */
    function url(string $path = ''): string
    {
        $base = config('app.url', '');
        $base = is_string($base) ? $base : '';
        $base = rtrim($base, '/');
        $path = ltrim($path, '/');
        return $path === '' ? $base : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Generate an absolute URL to an asset under the public directory.
     */
    function asset(string $path, bool $withVersion = false): string
    {
        $full = url($path);
        if ($withVersion) {
            $ver = config('app.version', '');
            $ver = is_string($ver) ? $ver : '';
            if ($ver !== '') {
                $separator = str_contains($full, '?') ? '&' : '?';
                $full .= $separator . 'ver=' . rawurlencode($ver);
            }
        }
        return $full;
    }
}

if (!function_exists('env')) {
    /**
     * Read an environment variable through the application's Environment, so
     * config files and services cast values the same way.
     */
    function env(string $key, mixed $default = null): mixed
    {
        static $fallback = null;
        if (!$fallback instanceof \Denosys\Config\Environment) {
            $fallback = new \Denosys\Config\Environment();
        }

        return (Application::getInstance()?->getEnvironment() ?? $fallback)->get($key, $default);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base of the application.
     */
    function base_path(string $path = ''): string
    {
        try {
            $basePath = container('path.base');
        } catch (Throwable $e) {
            $basePath = dirname(__DIR__);
        }

        return $basePath . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : '');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage folder.
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public folder.
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('storage')) {
    /**
     * Get a filesystem disk instance.
     *
     * @param string|null $disk The disk name, or null for default
     * @return \Denosys\Filesystem\FilesystemInterface|\Denosys\Filesystem\FilesystemManager
     */
    function storage(?string $disk = null): \Denosys\Filesystem\FilesystemInterface|\Denosys\Filesystem\FilesystemManager
    {
        /** @var \Denosys\Filesystem\FilesystemManager $manager */
        $manager = container(\Denosys\Filesystem\FilesystemManager::class);

        if ($disk !== null) {
            return $manager->disk($disk);
        }

        return $manager;
    }
}

if (!function_exists('session')) {
    /**
     * Get the session instance or a session value.
     *
     * @param string|array<string, mixed>|null $key Key to get, array of key/values to set, or null for session instance
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    function session(string|array|null $key = null, mixed $default = null): mixed
    {
        /** @var \Denosys\Session\SessionInterface $session */
        $session = container(\Denosys\Session\SessionInterface::class);

        if (null === $key) {
            return $session;
        }

        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $session->put($k, $v);
            }

            return null;
        }

        // Check flash data first, then regular session data
        if ($session->hasFlash($key)) {
            return $session->getFlash($key, $default);
        }

        return $session->get($key, $default);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token from the session.
     *
     * @return string
     */
    function csrf_token(): string
    {
        return session()->token();
    }
}

if (!function_exists('auth')) {
    /**
     * Get the authenticator instance.
     *
     * @return \Denosys\Auth\Authentication\Authenticator
     */
    function auth(): \Denosys\Auth\Authentication\Authenticator
    {
        return container(\Denosys\Auth\Authentication\Authenticator::class);
    }
}

if (!function_exists('event')) {
    /**
     * Dispatch an event.
     *
     * @param object $event The event to dispatch
     * @return object The event after listeners have processed it
     */
    function event(object $event): object
    {
        return container('events')->dispatch($event);
    }
}

if (!function_exists('session_driver')) {
    /**
     * Get the current session driver name.
     *
     * @return string|null The driver name or null if not available
     */
    function session_driver(): ?string
    {
        try {
            /** @var \Denosys\Session\SessionManager $manager */
            $manager = container(\Denosys\Session\SessionManager::class);
            return $manager->getDriver();
        } catch (Throwable) {
            return null;
        }
    }
}

if (!function_exists('route')) {
    /**
     * Generate a URL for a named route or path.
     *
     * @param  string  $name  Route name or path
     * @param  array<string, string|int>  $parameters  Parameters to substitute in the route
     *
     * @return string
     */
    function route(string $name, array $parameters = []): string
    {
        $baseUrl = config('app.url', '');

        if (str_starts_with($name, '/')) {
            $path = $name;
        } else {
            try {
                // First try using the UrlGenerator service
                $container = container();
                if ($container->has('url')) {
                    /** @var \Denosys\Routing\UrlGeneratorInterface $url */
                    $url = $container->get('url');
                    return $url->route($name, $parameters);
                }
                
                // Fallback: get routes from router directly
                /** @var \Denosys\Routing\Router $router */
                $router = $container->get('router');
                $routes = $router->getRouteCollection();

                $path = null;
                foreach ($routes->all() as $route) {
                    if ($route->getName() === $name) {
                        $path = $route->getPattern();
                        break;
                    }
                }

                if ($path === null) {
                    $path = '/' . ltrim($name, '/');
                }
            } catch (\Throwable $e) {
                $path = '/' . ltrim($name, '/');
            }
        }

        foreach ($parameters as $key => $value) {
            $path = str_replace('{' . $key . '}', (string) $value, $path);
        }

        return rtrim($baseUrl, '/') . $path;
    }
}

if (!function_exists('flash')) {
    /**
     * Get or set flash messages
     *
     * @param  string|null  $key  Flash message key/type
     * @param  string|null  $message  Flash message content
     *
     * @return mixed
     */
    function flash(?string $key = null, ?string $message = null): mixed
    {
        /** @var \Denosys\Session\SessionInterface $session */
        $session = container(\Denosys\Session\SessionInterface::class);

        if (null !== $key && null !== $message) {
            $session->flash($key, $message);
            
            return null;
        }

        if (null !== $key) {
            return $session->getFlash($key);
        }

        // Get all flash messages
        return $session->getFlashAll();
    }
}

if (!function_exists('encrypt')) {
    /**
     * Encrypt the given value.
     *
     * @param mixed $value The value to encrypt
     * @param bool $serialize Whether to serialize the value (default: true)
     *
     * @return string The encrypted string
     *
     * @throws \Denosys\Encryption\EncryptException
     */
    function encrypt(mixed $value, bool $serialize = true): string
    {
        /** @var \Denosys\Encryption\EncrypterInterface $encrypter */
        $encrypter = container(\Denosys\Encryption\EncrypterInterface::class);

        return $encrypter->encrypt($value, $serialize);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt the given payload.
     *
     * @param string $payload The encrypted payload to decrypt
     * @param bool $unserialize Whether to unserialize the decrypted value (default: true)
     *
     * @return mixed The decrypted value
     *
     * @throws \Denosys\Encryption\DecryptException
     */
    function decrypt(string $payload, bool $unserialize = true): mixed
    {
        /** @var \Denosys\Encryption\EncrypterInterface $encrypter */
        $encrypter = container(\Denosys\Encryption\EncrypterInterface::class);

        return $encrypter->decrypt($payload, $unserialize);
    }
}

if (!function_exists('encrypter')) {
    /**
     * Get the encrypter instance.
     *
     * @return \Denosys\Encryption\EncrypterInterface
     */
    function encrypter(): \Denosys\Encryption\EncrypterInterface
    {
        return container(\Denosys\Encryption\EncrypterInterface::class);
    }
}

if (!function_exists('mailer')) {
    /**
     * Get the mailer instance.
     *
     * @return \Symfony\Component\Mailer\MailerInterface
     */
    function mailer(): \Symfony\Component\Mailer\MailerInterface
    {
        return container(\Symfony\Component\Mailer\MailerInterface::class);
    }
}

if (!function_exists('dispatch')) {
    /**
     * Dispatch a job to the queue.
     *
     * @return string The job UUID
     */
    function dispatch(\Denosys\Queue\Job $job): string
    {
        return container(\Denosys\Queue\QueueManager::class)->dispatch($job);
    }
}
