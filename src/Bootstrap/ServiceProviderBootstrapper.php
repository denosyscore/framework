<?php

declare(strict_types=1);

namespace Denosys\Bootstrap;

use Denosys\Container\ContainerInterface;
use Denosys\Framework\DeferrableProvider;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Denosys\Container\Exceptions\ContainerResolutionException;

class ServiceProviderBootstrapper
{
    /** @var string[] */
    /** @var array<string, mixed> */

    private array $loadedProviders = [];

    /** @var Provider[] */
    /** @var array<string, mixed> */

    private array $providerInstances = [];

    /** @var string[] Additional providers set via fluent API */
    /** @var array<string, mixed> */

    /** @var list<class-string<Provider>> */
    private array $providers = [];

    /** @var array<string, string> Map of service => deferred provider class */
    /** @var array<string, mixed> */

    private array $deferredServices = [];

    private ?EventDispatcherInterface $dispatcher = null;

    public function __construct(protected readonly ContainerInterface $container) {}
    /**
     * The complete, ordered provider list. Duplicates are registered once.
     *
     * @param list<class-string<Provider>> $providers
     */
    public function setProviders(array $providers): void
    {
        $this->providers = array_values(array_unique($providers));
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
        $allProviders = $this->providers;

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
     * @param list<class-string<Provider>> $providers
     * @return array{list<class-string<Provider>>, list<class-string<Provider>>}
     */
    private function partitionProviders(array $providers): array
    {
        $eager = [];
        $deferred = [];

        foreach ($providers as $providerClass) {
            if (!class_exists($providerClass) || !is_subclass_of($providerClass, Provider::class)) {
                throw new \InvalidArgumentException("Service provider [{$providerClass}] does not exist or does not implement " . Provider::class . '.');
            }

            if (is_subclass_of($providerClass, DeferrableProvider::class)) {
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
            /** @var DeferrableProvider $instance */
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
     * Register a single provider (Phase 1).
     *
     * @throws ContainerResolutionException
     */
    private function registerProvider(string $providerClass): void
    {
        if (in_array($providerClass, $this->loadedProviders, true)) {
            return;
        }

        /** @var Provider $provider */
        $provider = $this->container->get($providerClass);

        // Register services
        $provider->register($this->container);

        // Store instance for boot phase
        $this->providerInstances[] = $provider;
        $this->loadedProviders[] = $providerClass;
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
