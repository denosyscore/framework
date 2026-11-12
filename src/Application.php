<?php

declare(strict_types=1);

namespace Denosys;

use Composer\InstalledVersions;
use Closure;
use RuntimeException;
use Throwable;
use Denosys\Framework\Http\Kernel;
use Denosys\Framework\Provider;
use Denosys\Console\Kernel as ConsoleKernel;
use Denosys\Container\Container;
use Denosys\Config\ConfigLoader;
use Symfony\Component\Filesystem\Path;
use Psr\Http\Message\ResponseInterface;
use Denosys\Container\ContainerInterface;
use Denosys\Container\Exceptions\NotFoundException;
use Denosys\Config\Repository;
use Denosys\Contracts\Config\ConfigReader;
use Denosys\Container\Exceptions\ContainerException;
use Psr\Http\Message\ServerRequestInterface;
use Denosys\Config\Environment;
use Denosys\Bootstrap\ServiceProviderBootstrapper;
use Denosys\Container\Exceptions\ContainerResolutionException;
use Psr\Container\ContainerInterface as PsrContainerInterface;

class Application
{
    /**
     * The framework version.
     */

    protected static ?Application $instance = null;

    protected Container $container;
    protected Environment $environment;
    protected ?Kernel $httpKernel = null;

    private bool $booted = false;

    /** @var list<class-string<Provider>> */
    private array $withoutProviders = [];

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

    protected Repository $config;

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

        $this->environment = new Environment();
        $this->environment->load($this->basePath);

        $loader = new ConfigLoader();
        $this->config = $loader->fromCache($this->configCacheFile())
            ?? (is_dir($this->configPath()) ? $loader->fromDirectory($this->configPath()) : new Repository());

        $this->registerBaseBindings();
    }

    private function createContainer(): Container
    {
        return new Container();
    }

    /**
     * Start configuring an application. Chain the with*() methods, then call
     * create(); nothing is booted until the first request or command.
     */
    public static function configure(string $basePath): self
    {
        return new self($basePath);
    }

    /**
     * Finish configuration. The application boots lazily on first use.
     */
    public function create(): self
    {
        return $this;
    }

    /**
     * The framework's providers, in dependency order. Applications get all of
     * them unless they remove some with without().
     *
     * @return list<class-string<Provider>>
     */
    public static function defaultProviders(): array
    {
        return [
            \Denosys\Framework\Providers\LoggerServiceProvider::class,
            \Denosys\Framework\Providers\ExceptionHandlerServiceProvider::class,
            \Denosys\Framework\Providers\DatabaseServiceProvider::class,
            \Denosys\Framework\Providers\MigrationServiceProvider::class,
            \Denosys\Framework\Providers\EventServiceProvider::class,
            \Denosys\Framework\Providers\EncryptionServiceProvider::class,
            \Denosys\Framework\Providers\HashingServiceProvider::class,
            \Denosys\Framework\Providers\SessionServiceProvider::class,
            \Denosys\Framework\Providers\CacheServiceProvider::class,
            \Denosys\Framework\Providers\RateLimiterServiceProvider::class,
            \Denosys\Framework\Providers\FilesystemServiceProvider::class,
            \Denosys\Framework\Providers\QueueServiceProvider::class,
            \Denosys\Framework\Providers\MailServiceProvider::class,
            \Denosys\Framework\Providers\AuthServiceProvider::class,
            \Denosys\Framework\Providers\ValidationServiceProvider::class,
            \Denosys\Framework\Providers\ViewServiceProvider::class,
            \Denosys\Framework\Providers\HttpServiceProvider::class,
            \Denosys\Framework\Providers\RouterServiceProvider::class,
            \Denosys\Framework\Providers\SecurityHeadersServiceProvider::class,
        ];
    }

    /**
     * Leave default providers out, for example a view engine the application
     * does not use.
     *
     * @param class-string<Provider> ...$providers
     */
    public function without(string ...$providers): static
    {
        $this->withoutProviders = array_values(array_unique([...$this->withoutProviders, ...$providers]));

        return $this;
    }

    /**
     * Every provider this application registers: the defaults it keeps, then
     * its own, each once.
     *
     * @return list<class-string<Provider>>
     */
    public function providers(): array
    {
        $own = $this->pendingProviders instanceof Closure ? ($this->pendingProviders)() : ($this->pendingProviders ?? []);
        if (!is_array($own)) {
            throw new \LogicException('withProviders() must be given, or its closure must return, a list of provider classes.');
        }

        $providers = array_values(array_diff(self::defaultProviders(), $this->withoutProviders));
        foreach ($own as $provider) {
            if (!is_string($provider) || !is_subclass_of($provider, Provider::class)) {
                throw new \InvalidArgumentException(sprintf(
                    'Service provider [%s] does not exist or does not implement %s.',
                    is_string($provider) ? $provider : get_debug_type($provider),
                    Provider::class,
                ));
            }
            if (!in_array($provider, $providers, true)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    /**
     * Register and boot every provider, once, with one event dispatcher.
     * HTTP requests and console commands both start here.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }
        $this->booted = true;

        $legacyProviders = $this->basePath('bootstrap/providers.php');
        if (is_file($legacyProviders) && (require $legacyProviders) !== []) {
            throw new \LogicException(
                'bootstrap/providers.php is no longer read. Move its providers to Application::configure()->withProviders() in bootstrap/app.php and delete the file.'
            );
        }

        $this->bindPendingConfigurations();

        $dispatcher = new Events\Dispatcher(new Events\ListenerProvider());
        $this->container->instance(Events\Dispatcher::class, $dispatcher);
        $this->container->instance(\Psr\EventDispatcher\EventDispatcherInterface::class, $dispatcher);
        $this->container->alias('events', \Psr\EventDispatcher\EventDispatcherInterface::class);

        $bootstrapper = new ServiceProviderBootstrapper($this->container);
        $bootstrapper->setProviders($this->providers());
        $this->container->instance(ServiceProviderBootstrapper::class, $bootstrapper);
        $bootstrapper->bootstrap($dispatcher);
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

        if (!$container->has(ConfigReader::class)) {
            $this->bindConfigurationReader($container);
        }
    }

    public static function getContainer(): ContainerInterface
    {
        if (!static::$instance) {
            throw new RuntimeException("Application has not been initialized");
        }

        return static::$instance->container;
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
        return $this->httpKernel()->handle($request);
    }

    private function httpKernel(): Kernel
    {
        $this->boot();

        return $this->httpKernel ??= new Kernel($this->container);
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


    public function isLocal(): bool
    {
        return $this->config->get('app.env') === 'local';
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
        return InstalledVersions::getPrettyVersion('denosyscore/framework') ?? 'dev';
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
        $this->httpKernel()->run($request);
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
        $this->boot();
        
        // Resolve console kernel class from config to avoid hard App coupling.
        $kernelClass = $this->resolveConsoleKernelClass();
        $consoleKernel = new $kernelClass($this->container, $this->basePath, self::consoleCommands());
        return $consoleKernel->handle();
    }

    /**
     * Console commands owned by the framework rather than the console package.
     *
     * @return list<class-string<\Denosys\Console\CommandInterface>>
     */
    public static function consoleCommands(): array
    {
        return [
            \Denosys\Framework\Console\ConfigCacheCommand::class,
            \Denosys\Framework\Console\ConfigClearCommand::class,
            \Denosys\Framework\Console\RoutesCacheCommand::class,
            \Denosys\Framework\Console\RoutesClearCommand::class,
            \Denosys\Framework\Console\ViewClearCommand::class,
        ];
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
     * Get environment configuration
     */
    public function getEnvironment(): Environment
    {
        return $this->environment;
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

        $this->container->instance(Repository::class, $this->config);
        $this->bindConfigurationReader($this->container);
        $this->container->alias('config', Repository::class);

        $this->container->instance(Environment::class, $this->environment);
        $this->container->alias('environment', Environment::class);

        $this->registerPathBindings();
    }

    private function bindConfigurationReader(ContainerInterface $container): void
    {
        $container->bind(
            ConfigReader::class,
            static fn (ContainerInterface $container): ConfigReader => $container->get(Repository::class)
        );
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
        $this->container->instance('path.view', $this->basePath('resources/views'));
    }
}
