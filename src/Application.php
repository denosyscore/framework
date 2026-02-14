<?php

declare(strict_types=1);

namespace Denosys;

use Closure;
use RuntimeException;
use Throwable;
use Denosys\Http\Kernel;
use Denosys\Console\Kernel as ConsoleKernel;
use Denosys\Container\Container;
use Denosys\Config\Configuration;
use Symfony\Component\Filesystem\Path;
use Psr\Http\Message\ResponseInterface;
use Denosys\Container\ContainerInterface;
use Denosys\Container\Exceptions\NotFoundException;
use Denosys\Config\ConfigurationInterface;
use Denosys\Container\Exceptions\ContainerException;
use Psr\Http\Message\ServerRequestInterface;
use Denosys\Environment\EnvironmentManager;
use Denosys\Bootstrap\ServiceProviderBootstrapper;
use Denosys\Container\Exceptions\ContainerResolutionException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class Application
{
    /**
     * The framework version.
     */
    protected const VERSION = '0.0.1';

    protected static ?Application $instance = null;

    protected Container $container;
    protected EnvironmentManager $environment;
    protected ?Kernel $httpKernel = null;

    /**
     * The app public path.
     */
    protected ?string $publicPath = null;

    /**
     * The app storage path.
     */
    protected ?string $storagePath = null;

    /**
     * The app config path
     */
    protected ?string $configPath = null;

    /**
     * The app storage cache path
     */
    protected ?string $storageCachePath = null;

    protected ConfigurationInterface $config;

    /**
     * The application's namespace.
     */
    protected ?string $namespace = null;

    /**
     * Pending middleware configuration (set via fluent API).
     *
     * @var array<class-string>|Closure|null
     */
    protected array|Closure|null $pendingMiddleware = null;

    /**
     * Pending service providers (set via fluent API).
     *
     * @var array<class-string>|Closure|null
     */
    protected array|Closure|null $pendingProviders = null;

    /**
     * Pending routes configuration (set via fluent API).
     *
     * @var array<string, string|Closure>|null
     */
    protected ?array $pendingRoutes = null;

    /**
     * Pending exception handling configuration (set via fluent API).
     *
     * @var array<string, mixed>|Closure|null
     */
    protected array|Closure|null $pendingExceptions = null;

    /**
     * @throws ContainerException
     * @throws NotFoundException
     * @throws ContainerResolutionException
     */
    public function __construct(public string $basePath)
    {
        $this->container = $this->createContainer();
        
        // Set instance FIRST so container() helper works
        static::setInstance($this);
        
        // Register path bindings early so storage_path(), base_path(), etc.
        // work correctly when config files are loaded
        $this->registerPathBindings();

        $this->environment = new EnvironmentManager();
        $this->environment->load($this->basePath);

        $this->config = (new Configuration(
            $this->configPath(),
            $this->configCacheFile()
        ));

        $this->config->loadFiles();

        $this->registerBaseBindings();
    }

    private function createContainer(): Container
    {
        $cacheFile = $this->basePath('storage/core/cache/container.php');
        $compiledClass = 'Denosys\Container\Compiled\CompiledContainer';

        if (!is_file($cacheFile)) {
            return new Container();
        }

        try {
            require_once $cacheFile;

            if (class_exists($compiledClass) && is_subclass_of($compiledClass, Container::class)) {
                /** @var Container $container */
                $container = new $compiledClass();
                return $container;
            }
        } catch (Throwable) {
            // Fall back to standard container if cache is invalid.
        }

        return new Container();
    }

    /**
     * Configure middleware for the application.
     *
     * @param array<class-string>|Closure $middleware Array of middleware classes or a Closure for custom configuration
     */
    public function withMiddleware(array|Closure $middleware): static
    {
        $this->pendingMiddleware = $middleware;

        return $this;
    }

    /**
     * Configure additional service providers.
     *
     * @param array<class-string>|Closure $providers Array of provider classes or a Closure for custom configuration
     */
    public function withProviders(array|Closure $providers): static
    {
        $this->pendingProviders = $providers;

        return $this;
    }

    /**
     * Configure routes for the application.
     *
     * Supports named parameters for different route types:
     * - web: Path to web routes file
     * - api: Path to API routes file (optional)
     * - using: Closure for custom route configuration
     *
     * @param string|null $web Path to web routes file
     * @param string|null $api Path to API routes file
     * @param Closure|null $using Closure for custom route configuration
     */
    public function withRoutes(
        ?string $web = null,
        ?string $api = null,
        ?Closure $using = null
    ): static {
        $this->pendingRoutes = array_filter([
            'web' => $web,
            'api' => $api,
            'using' => $using,
        ], fn($value) => $value !== null);

        return $this;
    }

    /**
     * Configure exception handling.
     *
     * @param array<string, callable>|Closure $exceptions Array of exception handlers or a Closure for custom configuration
     */
    public function withExceptions(array|Closure $exceptions): static
    {
        $this->pendingExceptions = $exceptions;

        return $this;
    }

    public static function setInstance(self $app): void
    {
        static::$instance = $app;
    }

    public static function getInstance(): ?self
    {
        return static::$instance;
    }

    public static function resetInstance(): void
    {
        static::$instance = null;
    }

    public function setContainer(Container $container): void
    {
        $this->container = $container;
    }

    public static function getContainer(): ContainerInterface
    {
        if (!static::$instance) {
            throw new RuntimeException("Application has not been initialized");
        }

        return static::$instance->container;
    }

    /**
     * Bootstrap the application
     *
     * @throws ContainerException
     */
    public function bootstrap(): void
    {
        if (null !== $this->httpKernel) {
            return;
        }

        // Bind pending configurations to container so providers can access them
        $this->bindPendingConfigurations();

        $bootstrapper = new ServiceProviderBootstrapper($this->container);

        // Set additional providers if configured via fluent API
        if ($this->pendingProviders !== null) {
            $providers = $this->pendingProviders instanceof Closure
                ? ($this->pendingProviders)()
                : $this->pendingProviders;
            $bootstrapper->setAdditionalProviders($providers);
        }

        $this->httpKernel = new Kernel($this->container, $bootstrapper);
    }

    /**
     * Bind pending configurations to the container using typed objects.
     *
     * @throws ContainerException
     */
    protected function bindPendingConfigurations(): void
    {
        // Bind middleware configuration
        if ($this->pendingMiddleware !== null) {
            $middlewareConfig = new \Denosys\Bootstrap\Configuration\MiddlewareConfiguration();
            if ($this->pendingMiddleware instanceof Closure) {
                $middlewareConfig->using($this->pendingMiddleware);
            }
            $this->container->instance(
                \Denosys\Bootstrap\Configuration\MiddlewareConfiguration::class,
                $middlewareConfig
            );
        }

        // Bind routes configuration
        if ($this->pendingRoutes !== null && !empty($this->pendingRoutes)) {
            $routesConfig = new \Denosys\Bootstrap\Configuration\RoutesConfiguration();
            if (isset($this->pendingRoutes['web'])) {
                $routesConfig->web($this->pendingRoutes['web']);
            }
            if (isset($this->pendingRoutes['api'])) {
                $routesConfig->api($this->pendingRoutes['api']);
            }
            if (isset($this->pendingRoutes['using'])) {
                $routesConfig->using($this->pendingRoutes['using']);
            }
            $this->container->instance(
                \Denosys\Bootstrap\Configuration\RoutesConfiguration::class,
                $routesConfig
            );
        }

        // Bind exceptions configuration
        if ($this->pendingExceptions !== null) {
            $exceptionConfig = new \Denosys\Bootstrap\Configuration\ExceptionConfiguration();
            if ($this->pendingExceptions instanceof Closure) {
                $exceptionConfig->using($this->pendingExceptions);
            }
            $this->container->instance(
                \Denosys\Bootstrap\Configuration\ExceptionConfiguration::class,
                $exceptionConfig
            );
        }
    }

    /**
     * Handle HTTP request
     *
     * @throws ContainerResolutionException
     * @throws ContainerException
     */
    public function handleRequest(?ServerRequestInterface $request = null): ResponseInterface
    {
        $this->bootstrap();
        return $this->httpKernel->handle($request);
    }

    /**
     * Get the path to the application "app" directory.
     *
     * @param  string  $path  The path to join with the app path.
     *
     * @return string The joined path.
     */
    public function path(string $path = ''): string
    {
        return $this->joinPaths($this->basePath('app'), $path);
    }

    /**
     * Get the base path of the application.
     *
     * @param  string  $path  The path to join with the base path.
     *
     * @return string The joined path.
     */
    public function basePath(string $path = ''): string
    {
        return $this->joinPaths($this->basePath, $path);
    }

    /**
     * Join the base path with a given path.
     *
     * @param  string  $basePath  The base path of the application.
     * @param  string  $path  The path to join with the base path.
     *
     * @return string The joined path.
     */
    public function joinPaths(string $basePath, string $path = ''): string
    {
        return Path::join($basePath, $path);
    }

    /**
     * Get the path to the public / web directory.
     */
    public function publicPath(string $path = ''): string
    {
        return $this->joinPaths($this->publicPath ?: $this->basePath('public'), $path);
    }

    /**
     * Get the path to the config directory.
     */
    public function configPath(string $path = ''): string
    {
        return $this->joinPaths($this->configPath ?: $this->basePath('config'), $path);
    }

    /**
     * Get the path to the storage directory.
     */
    public function storagePath(string $path = ''): string
    {
        return $this->joinPaths($this->storagePath ?: $this->basePath('storage'), $path);
    }

    public function configCacheFile(string $path = ''): string
    {
        $path = $this->joinPaths($this->storageCachePath ?: $this->basePath('storage/core/cache'), $path);

        return rtrim($path, '/') . '/config.php';
    }

    public function routeCacheFile(string $path = ''): string
    {
        $path = $this->joinPaths($this->storageCachePath ?: $this->basePath('storage/core/cache'), $path);

        return rtrim($path, '/') . '/routes.php';
    }

    public function containerCacheFile(string $path = ''): string
    {
        $path = $this->joinPaths($this->storageCachePath ?: $this->basePath('storage/core/cache'), $path);

        return rtrim($path, '/') . '/container.php';
    }

    public function isLocal(): bool
    {
        return $_ENV['APP_ENV'] === 'local';
    }

    /**
     * Get the application namespace.
     *
     * @throws RuntimeException
     */
    public function getNamespace(): string
    {
        if (null !== $this->namespace) {
            return $this->namespace;
        }

        $composer = json_decode(file_get_contents($this->basePath('composer.json')), true);

        foreach ((array) $composer['autoload']['psr-4'] as $namespace => $path) {
            foreach ((array) $path as $pathChoice) {
                if (realpath($this->path()) === realpath($this->basePath($pathChoice))) {
                    return $this->namespace = $namespace;
                }
            }
        }

        throw new RuntimeException('Unable to detect application namespace.');
    }

    public function version(): string
    {
        return static::VERSION;
    }

    /**
     * Run the application (auto-detects HTTP vs CLI context).
     *
     * This is the unified entry point for both web and console applications.
     * It detects the runtime environment and delegates to the appropriate handler.
     *
     * @return int Exit code (0 for HTTP, actual exit code for CLI)
     * @throws ContainerResolutionException
     * @throws ContainerException
     */
    public function run(): int
    {
        if ($this->runningInConsole()) {
            return $this->runConsole();
        }
        
        $this->runHttp();
        return 0;
    }

    /**
     * Determine if the application is running in the console.
     */
    public function runningInConsole(): bool
    {
        return \PHP_SAPI === 'cli' || \PHP_SAPI === 'phpdbg';
    }

    /**
     * Run HTTP application (handle + emit response)
     *
     * @throws ContainerResolutionException
     * @throws ContainerException
     */
    public function runHttp(?ServerRequestInterface $request = null): void
    {
        $this->bootstrap();
        $this->httpKernel->run($request);
    }

    /**
     * Run console application
     *
     * Bootstraps all services (same as web) and runs the console kernel.
     * This ensures console commands have access to the same container
     * and services as web requests.
     *
     * @return int Exit code
     */
    public function runConsole(): int
    {
        // Bootstrap services exactly like the web path does
        $this->bootstrapServices();
        
        // Resolve console kernel class from config to avoid hard App coupling.
        $kernelClass = $this->resolveConsoleKernelClass();
        $consoleKernel = new $kernelClass($this->container, $this->basePath);
        return $consoleKernel->handle();
    }

    /**
     * Resolve console kernel class name.
     *
     * @return class-string<ConsoleKernel>
     */
    protected function resolveConsoleKernelClass(): string
    {
        $kernelClass = $this->config->get('console.kernel');

        if (is_string($kernelClass) && $kernelClass !== '') {
            if (!class_exists($kernelClass)) {
                throw new RuntimeException("Configured console kernel class [{$kernelClass}] does not exist.");
            }

            if (!is_subclass_of($kernelClass, ConsoleKernel::class)) {
                throw new RuntimeException(
                    "Configured console kernel class [{$kernelClass}] must extend [" . ConsoleKernel::class . '].'
                );
            }

            return $kernelClass;
        }

        return \App\Console\Kernel::class;
    }

    /**
     * Bootstrap application services without creating HTTP kernel.
     *
     * This is shared between web and console entry points to ensure
     * consistent service registration.
     *
     * @throws ContainerException
     */
    protected function bootstrapServices(): void
    {
        // Bind pending configurations to container so providers can access them
        $this->bindPendingConfigurations();

        // Create event dispatcher
        $listenerProvider = new Events\ListenerProvider();
        $dispatcher = new Events\Dispatcher($listenerProvider);
        
        $this->container->instance(Events\Dispatcher::class, $dispatcher);
        $this->container->instance(\Psr\EventDispatcher\EventDispatcherInterface::class, $dispatcher);
        $this->container->alias('events', \Psr\EventDispatcher\EventDispatcherInterface::class);

        // Create bootstrapper and set providers
        $bootstrapper = new ServiceProviderBootstrapper($this->container);

        if ($this->pendingProviders !== null) {
            $providers = $this->pendingProviders instanceof Closure
                ? ($this->pendingProviders)()
                : $this->pendingProviders;
            $bootstrapper->setAdditionalProviders($providers);
        }

        // Bootstrap all services
        $bootstrapper->bootstrap($dispatcher);
    }


    /**
     * Get the HTTP kernel (creates if needed)
     */
    public function getHttpKernel(): Kernel
    {
        $this->bootstrap();
        return $this->httpKernel;
    }

    /**
     * Get environment configuration
     */
    public function getEnvironment(): EnvironmentManager
    {
        return $this->environment;
    }

    /**
     * Check if running in console mode
     */
    public function isConsole(): bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    /**
     * @throws NotFoundException
     * @throws ContainerException
     * @throws ContainerResolutionException
     */
    protected function registerBaseBindings(): void
    {
        static::setInstance($this);

        $this->container->instance(Application::class, $this);
        $this->container->alias('app', Application::class);

        $this->container->instance(ContainerInterface::class, $this->container);
        $this->container->instance(PsrContainerInterface::class, $this->container);
        $this->container->instance(Container::class, $this->container);

        $this->container->instance(ConfigurationInterface::class, $this->config);
        $this->container->alias('config', ConfigurationInterface::class);

        $this->container->instance(EnvironmentManager::class, $this->environment);
        $this->container->alias('environment', EnvironmentManager::class);

        $this->registerPathBindings();
    }

    /**
     * @throws ContainerException
     */
    protected function registerPathBindings(): void
    {
        $this->container->instance('path', $this->path());
        $this->container->instance('path.base', $this->basePath());
        $this->container->instance('path.bootstrap', $this->basePath('bootstrap'));
        $this->container->instance('path.config', $this->configPath());
        $this->container->instance('path.public', $this->publicPath());
        $this->container->instance('path.storage', $this->storagePath());
        $this->container->instance('path.view', $this->basePath('app/Views'));
    }
}
