<?php

declare(strict_types=1);

namespace Denosys\Logging;

use Denosys\Container\ContainerInterface;
use Denosys\Contracts\ServiceProviderInterface;
use Denosys\Container\Exceptions\NotFoundException;
use Denosys\Container\Exceptions\ContainerException;
use Denosys\Environment\EnvironmentManager;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use Denosys\Container\Exceptions\ContainerResolutionException;

class LoggerServiceProvider implements ServiceProviderInterface
{
    /**
     * @throws NotFoundException
     * @throws ContainerResolutionException
     * @throws ContainerException
     */
    public function register(ContainerInterface $container): void
    {
        /** @var EnvironmentManager $environmentManager */
        $environmentManager = $container->get(EnvironmentManager::class);
        $environment = $environmentManager->get('APP_ENV', 'production');
        $logPath = $environmentManager->get('LOG_PATH') ?: $container->get('path.base') . '/storage/logs/app.log';
        $level   = ($environment === 'production') ? Level::Warning : Level::Debug;

        $logHandler = new StreamHandler(
            $logPath,
            $level
        );

        $logHandler->setFormatter(new LineFormatter(
            null,
            'Y-m-d H:i:s',
            true,
            true,
            true
        ));

        $logger = new Logger($environment, [
            $logHandler,
        ], [
            new PsrLogMessageProcessor()
        ]);

        $container->instance(LoggerInterface::class, $logger);
        $container->alias('logger', LoggerInterface::class);
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }
}
