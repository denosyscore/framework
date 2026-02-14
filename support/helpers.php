<?php

declare(strict_types=1);

use Mailgun\Mailgun;
use Denosys\Application;
use Denosys\Config\ConfigurationInterface;
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
        /** @var ConfigurationInterface $config */
        $config = container(ConfigurationInterface::class);

        if ($key === null) {
            return $config;
        }

        return $config->get($key, $default);
    }
}

if (!function_exists('send_email')) {
    /**
     * Send an email using the Mailgun API.
     *
     * @param  string  $to
     * @param  string  $subject
     * @param  string  $text
     * @param  string|null  $html
     *
     * @return bool
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    function send_email(string $to, string $subject, string $text, ?string $html = null): bool
    {
        $domain = config('mail.mailgun.domain', '');
        $apiKey = config('mail.mailgun.api_key', '');
        $fromAddress = config('mail.from.address', 'noreply@localhost');
        $fromName = config('mail.from.name', config('app.name', 'Denosys Framework'));

        if (empty($apiKey) || empty($domain)) {
            error_log('Mailgun API key or domain is not configured.');
            return false;
        }

        $mailgun = Mailgun::create($apiKey);

        try {
            $mailgun->messages()->send(
                $domain,
                [
                    'from' => sprintf('%s <%s>', $fromName, $fromAddress),
                    'to' => $to,
                    'subject' => $subject,
                    'text' => $text,
                    'html' => $html,
                ]
            );

            return true;
        } catch (Throwable $e) {
            error_log('Mailgun error: ' . $e->getMessage());

            return false;
        }
    }
}

if (!function_exists('send_email_with_template')) {
    /**
     * Send an email using a template.
     *
     * @param  string  $to
     * @param  string  $subject
     * @param  string  $template
     * @param  array<string, mixed>  $data
     *
     * @return bool
     */
    function send_email_with_template(string $to, string $subject, string $template, array $data = []): bool
    {
        $html = render_template($template, $data);
        $text = strip_tags($html);

        return send_email($to, $subject, $text, $html);
    }
}

if (!function_exists('render_template')) {
    /**
     * Render a template with data.
     *
     * @param  string  $template
     * @param  array<string, mixed>  $data
     *
     * @return string
     */
    function render_template(string $template, array $data = []): string
    {
        extract($data);
        ob_start();
        include __DIR__ . "/templates/{$template}.php";
        return ob_get_clean();
    }
}

if (!function_exists('url')) {
    /**
     * Build an absolute URL from the base app URL and a relative path.
     */
    function url(string $path = ''): string
    {
        $base = (string) config('app.url', '');
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
            $ver = (string) config('app.version', '');
            if ($ver !== '') {
                $separator = str_contains($full, '?') ? '&' : '?';
                $full .= $separator . 'ver=' . rawurlencode($ver);
            }
        }
        return $full;
    }
}

if (!function_exists('format_currency')) {
    /**
     * Format a number as currency.
     *
     * @param  float  $amount
     *
     * @return string
     */
    function format_currency(float $amount): string
    {
        return '$' . number_format($amount, 2, '.', ',');
    }
}

if (!function_exists('rand_float')) {
    /**
     * Generate a random float between two numbers.
     *
     * @param  float  $startNumber
     * @param  float  $endNumber
     * @param  int  $multiple
     *
     * @return float
     */
    function rand_float(float $startNumber = 25, float $endNumber = 57, int $multiple = 1000000): float
    {
        if ($startNumber > $endNumber) {
            throw new InvalidArgumentException('Start number must be less than or equal to end number.');
        }

        return mt_rand((int) ($startNumber * $multiple), (int) ($endNumber * $multiple)) / $multiple;
    }
}

if (!function_exists('env')) {
    /**
     * Resolve environment values using the application's EnvironmentManager
     * with safe fallback when container isn't available.
     *
     * @param mixed $default
     */
    function env(string $key, $default = null): mixed
    {
        $value = $_ENV[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $value,
        };
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

if (!function_exists('render_flash')) {
    /**
     * Render flash messages with custom styling
     *
     * @param  string|null  $type  Specific flash message type to render
     * @param  array<string, mixed>  $options  Styling options
     *
     * @return string
     */
    function render_flash(?string $type = null, array $options = []): string
    {
        $html = '';
        $defaultOptions = [
            'framework' => 'bootstrap', // bootstrap, tailwind, custom
            'dismissible' => true,
            'fade' => true,
            'custom_classes' => [],
            'icons' => [
                'success' => '✓',
                'error' => '✗',
                'warning' => '⚠',
                'info' => 'ℹ',
            ],
        ];

        $options = array_merge($defaultOptions, $options);

        // Get flash messages using the flash() helper
        $allMessages = flash();

        if (empty($allMessages)) {
            return '';
        }

        $messages = $type !== null ? [$type => $allMessages[$type] ?? null] : $allMessages;

        foreach ($messages as $msgType => $message) {
            if ($message === null) {
                continue;
            }

            $classes = $options['framework'] === 'bootstrap'
                ? "alert alert-{$msgType}"
                : ($options['framework'] === 'tailwind'
                    ? "p-4 mb-4 text-sm rounded-lg " . ($msgType === 'success' ? 'text-green-800 bg-green-50' : ($msgType === 'error' ? 'text-red-800 bg-red-50' : ($msgType === 'warning' ? 'text-yellow-800 bg-yellow-50' : 'text-blue-800 bg-blue-50')))
                    : "flash flash-{$msgType}");

            if ($options['framework'] === 'bootstrap') {
                if ($options['dismissible']) {
                    $classes .= ' alert-dismissible';
                }
                if ($options['fade']) {
                    $classes .= ' fade show';
                }
            }

            if (!empty($options['custom_classes'][$msgType])) {
                $classes .= ' ' . $options['custom_classes'][$msgType];
            }

            $icon = $options['icons'][$msgType] ?? '';
            $iconHtml = $icon ? "<span class=\"flash-icon\">{$icon}</span> " : '';

            $closeButton = '';
            if ($options['dismissible']) {
                if ($options['framework'] === 'bootstrap') {
                    $closeButton = '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                } elseif ($options['framework'] === 'tailwind') {
                    $closeButton = '<button type="button" class="ml-auto -mx-1.5 -my-1.5 bg-green-50 text-green-500 rounded-lg focus:ring-2 focus:ring-green-400 p-1.5 hover:bg-green-200 inline-flex h-8 w-8" onclick="this.parentElement.remove()">×</button>';
                } else {
                    $closeButton = '<button type="button" class="flash-close" onclick="this.parentElement.remove()">×</button>';
                }
            }

            $html .= "<div class=\"{$classes}\" role=\"alert\">";
            $html .= $iconHtml . htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
            $html .= $closeButton;
            $html .= "</div>";
        }

        return $html;
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
     * @return \Denosys\Mail\Mailer
     */
    function mailer(): \Denosys\Mail\Mailer
    {
        return container(\Denosys\Mail\Mailer::class);
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
