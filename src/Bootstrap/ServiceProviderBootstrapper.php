<?php

declare(strict_types=1);

namespace Denosys\Bootstrap;

use Denosys\Container\ContainerInterface;
use Denosys\Contracts\DeferrableProviderInterface;
use Denosys\Contracts\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Denosys\Container\Exceptions\ContainerResolutionException;

class ServiceProviderBootstrapper
{
    /** @var string[] */
    /** @var array<string, mixed> */

    private array $loadedProviders = [];

    /** @var ServiceProviderInterface[] */
    /** @var array<string, mixed> */

    private array $providerInstances = [];

    /** @var string[] Additional providers set via fluent API */
    /** @var array<string, mixed> */

    private array $additionalProviders = [];

    /** @var array<string, string> Map of service => deferred provider class */
    /** @var array<string, mixed> */

    private array $deferredServices = [];

    private ?EventDispatcherInterface $dispatcher = null;

    public function __construct(protected readonly ContainerInterface $container) {}

    /**
     * Set additional service providers to be loaded after file-based providers.
     *
     * @param string[] $providers Array of provider class names
      * @param array<class-string> $providers
     */
    public function setAdditionalProviders(array $providers): void
    {
        $this->additionalProviders = $providers;
    }

    /**
     * Bootstrap all framework and application services.
     *
     * @throws ContainerResolutionException
     */
    public function bootstrap(EventDispatcherInterface $dispatcher): void
    {
        $this->dispatcher = $dispatcher;

        // Collect all providers
        $allProviders = $this->collectAllProviders();

        // Separate deferred from eager providers
        [$eagerProviders, $deferredProviders] = $this->partitionProviders($allProviders);

        // Register deferred providers for lazy loading
        $this->registerDeferredProviders($deferredProviders);

        // Phase 1: Register eager providers
        foreach ($eagerProviders as $providerClass) {
            $this->registerProvider($providerClass);
        }

        // Phase 2: Boot eager providers
        foreach ($this->providerInstances as $provider) {
            $provider->boot($this->container, $dispatcher);
        }
    }

    /**
     * Partition providers into eager and deferred.
     *
     * @param string[] $providers
     * @return array{0: string[], 1: string[]}
     */
    /**
     * @return array<string, mixed>
      * @param array<class-string> $providers
     */
private function partitionProviders(array $providers): array
    {
        $eager = [];
        $deferred = [];

        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass)) {
                continue;
            }

            if (is_subclass_of($providerClass, DeferrableProviderInterface::class)) {
                $deferred[] = $providerClass;
            } else {
                $eager[] = $providerClass;
            }
        }

        return [$eager, $deferred];
    }

    /**
     * Register deferred providers for lazy loading.
     *
     * @param string[] $providers
      * @param array<class-string> $providers
     */
    private function registerDeferredProviders(array $providers): void
    {
        foreach ($providers as $providerClass) {
            /** @var DeferrableProviderInterface $instance */
            $instance = new $providerClass();
            
            foreach ($instance->provides() as $service) {
                $this->deferredServices[$service] = $providerClass;
            }
        }

        // Hook into container resolution to load deferred providers
        $this->container->setDeferredResolver(function (string $abstract): void {
            $this->loadDeferredProvider($abstract);
        });
    }

    /**
     * Load a deferred provider when its service is requested.
     */
    public function loadDeferredProvider(string $abstract): void
    {
        if (!isset($this->deferredServices[$abstract])) {
            return;
        }

        $providerClass = $this->deferredServices[$abstract];

        // Already loaded
        if (in_array($providerClass, $this->loadedProviders, true)) {
            return;
        }

        // Register and boot the deferred provider
        $this->registerProvider($providerClass);

        // Boot immediately since we're past the boot phase
        foreach ($this->providerInstances as $provider) {
            if (get_class($provider) === $providerClass && $this->dispatcher !== null) {
                $provider->boot($this->container, $this->dispatcher);
                break;
            }
        }
    }

    /**
     * Collect all provider classes in correct order.
     *
     * @return string[]
     */
    private function collectAllProviders(): array
    {
        $providers = [];

        // Core framework providers (in dependency order)
        $providers = array_merge($providers, $this->getCoreProviders());

        // Application providers from bootstrap/providers.php
        $providers = array_merge($providers, $this->loadAppProviders());

        // Additional providers set via fluent API
        $providers = array_merge($providers, $this->additionalProviders);

        return $providers;
    }

    /**
     * Get core framework providers in dependency order.
     *
     * @return string[]
     */
    private function getCoreProviders(): array
    {
        return [
            \Denosys\Logging\LoggerServiceProvider::class,
            \Denosys\Http\Exceptions\ExceptionHandlerServiceProvider::class,
            \Denosys\Database\DatabaseServiceProvider::class,
            \Denosys\Database\Migration\MigrationServiceProvider::class,
            \Denosys\Events\EventServiceProvider::class,
            \Denosys\Encryption\EncryptionServiceProvider::class,
            \Denosys\Session\SessionServiceProvider::class,
            \Denosys\Cache\CacheServiceProvider::class,
            \Denosys\RateLimiter\RateLimiterServiceProvider::class,
            \Denosys\Auth\AuthServiceProvider::class,
            \Denosys\Validation\ValidationServiceProvider::class,
            \Denosys\View\ViewServiceProvider::class,
            \Denosys\Http\HttpServiceProvider::class,
            \Denosys\Routing\RouterServiceProvider::class,
            \Denosys\Http\Security\SecurityHeadersServiceProvider::class,
        ];
    }

    /**
     * Register a single provider (Phase 1).
     *
     * @throws ContainerResolutionException
     */
    private function registerProvider(string $providerClass): void
    {
        if (!class_exists($providerClass)) {
            return;
        }

        if (in_array($providerClass, $this->loadedProviders, true)) {
            return;
        }

        /** @var ServiceProviderInterface $provider */
        $provider = $this->container->get($providerClass);

        // Register services
        $provider->register($this->container);

        // Store instance for boot phase
        $this->providerInstances[] = $provider;
        $this->loadedProviders[] = $providerClass;
    }

    /**
     * Load providers configuration from file.
     *
     * @return string[]
     * @throws ContainerResolutionException
     */
    private function loadAppProviders(): array
    {
        $providersFile = $this->container->get('path.bootstrap') . '/providers.php';

        if (!file_exists($providersFile)) {
            return [];
        }

        $providers = require $providersFile;

        if (!is_array($providers)) {
            return [];
        }

        return $providers;
    }

    /**
     * Get list of loaded providers for debugging.
     *
     * @return string[]
     */
    public function getLoadedProviders(): array
    {
        return $this->loadedProviders;
    }

    /**
     * Get list of deferred providers and their services.
     *
     * @return array<string, string>
     */
    public function getDeferredServices(): array
    {
        return $this->deferredServices;
    }
}
