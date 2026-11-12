<?php

declare(strict_types=1);

namespace Denosys\Framework;


/**
 * Deferrable Provider Interface.
 * 
 * Service providers implementing this interface will only be loaded
 * when one of the services they provide is actually requested.
 * 
 * This improves application boot performance by deferring the registration
 * and bootstrapping of providers until their services are needed.
 * 
 * @example
 * class MailServiceProvider implements Provider, DeferrableProvider
 * {
 *     public function provides(): array
 *     {
 *         return [
 *             Mailer::class,
 *             MailerInterface::class,
 *             'mail',
 *         ];
 *     }
 * }
 */
interface DeferrableProvider
{
    /**
     * Get the services provided by the provider.
     * 
     * Return an array of service identifiers (class names, interface names, or aliases)
     * that this provider registers. The provider will only be loaded when one of these
     * services is requested from the container.
     * 
     * @return array<string> List of service identifiers this provider registers
     */
    public function provides(): array;
}
