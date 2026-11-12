<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Http\Exceptions\ExceptionHandler;
use Denosys\Container\ContainerInterface;
use Denosys\Container\Exceptions\ContainerResolutionException;
use Denosys\Container\Exceptions\NotFoundException;
use Denosys\Framework\Provider;
use Denosys\Bootstrap\Configuration\ExceptionConfiguration;
use Psr\Log\LoggerInterface;
use Denosys\Config\Repository;
use Psr\EventDispatcher\EventDispatcherInterface;

class ExceptionHandlerServiceProvider implements Provider
{
    /**
     * @throws NotFoundException
     * @throws ContainerResolutionException
     */
    public function register(ContainerInterface $container): void
    {
        $logger = $container->get(LoggerInterface::class);

        $config = $container->get(Repository::class);
        if (!$config instanceof Repository) {
            throw new \LogicException('The configuration repository is not bound.');
        }
        $debug = $config->get('app.debug') === true;
        $environment = $config->get('app.env');
        $environment = is_string($environment) && $environment !== '' ? $environment : 'production';

        $exceptionHandler = new ExceptionHandler(
            $container->get('path.base'),
            $logger,
            $debug,
            $environment
        );
        $exceptionHandler->register();

        $container->instance(ExceptionHandler::class, $exceptionHandler);
        $container->alias('exception.handler', ExceptionHandler::class);
    }

    /**
     * @throws ContainerResolutionException
     */
    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        $this->applyExceptionConfiguration($container);
    }

    /**
     * @throws ContainerResolutionException
     */
    private function applyExceptionConfiguration(ContainerInterface $container): void
    {
        if (!$container->has(ExceptionConfiguration::class)) {
            return;
        }

        $config = $container->get(ExceptionConfiguration::class);
        $handler = $container->get(ExceptionHandler::class);
        $config->apply($handler);
    }
}
