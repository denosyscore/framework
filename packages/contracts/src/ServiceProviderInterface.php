<?php

declare(strict_types=1);

namespace Denosys\Contracts;

use Denosys\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Service Provider Interface.
 * 
 * Service providers are the central place to configure your application.
 * They bootstrap any components your application requires, such as
 * database connections, event listeners, or middleware.
 * 
 * Lifecycle:
 * 1. register() - Bind services into the container. Other services may not be available yet.
 * 2. boot() - Perform any actions after ALL providers have been registered.
 *             Safe to resolve other services here.
 */
interface ServiceProviderInterface
{
    /**
     * Register bindings and services into the container.
     * 
     * This method is called BEFORE boot() on ANY provider. Use this to:
     * - Bind interfaces to implementations
     * - Register singletons
     * - Set up aliases
     * 
     * Do NOT resolve other services here - they may not be registered yet.
     */
    public function register(ContainerInterface $container): void;

    /**
     * Boot after ALL providers are registered.
     * 
     * This method is called AFTER register() has been called on ALL providers.
     * Safe to resolve other services from the container here.
     * 
     * Common uses:
     * - Subscribe to events
     * - Set up relationships between services
     * - Run initialization logic that depends on other services
     * 
     * @param ContainerInterface $container The application container
     * @param EventDispatcherInterface|null $dispatcher Event dispatcher (null in some CLI contexts)
     */
    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void;
}
