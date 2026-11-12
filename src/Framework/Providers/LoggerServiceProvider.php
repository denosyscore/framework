<?php

declare(strict_types=1);

namespace Denosys\Framework\Providers;

use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Denosys\Container\Exceptions\NotFoundException;
use Denosys\Container\Exceptions\ContainerException;
use Denosys\Config\Repository;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Psr\Log\LoggerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Processor\PsrLogMessageProcessor;
use Denosys\Container\Exceptions\ContainerResolutionException;

class LoggerServiceProvider implements Provider
{
    /**
     * @throws NotFoundException
     * @throws ContainerResolutionException
     * @throws ContainerException
     */
    public function register(ContainerInterface $container): void
    {
        // Read through configuration, like every other setting.
        $config = $container->get(Repository::class);
        if (!$config instanceof Repository) {
            throw new \LogicException('The configuration repository is not bound.');
        }
        $environment = $config->get('app.env');
        $environment = is_string($environment) && $environment !== '' ? $environment : 'production';
        $logPath = $config->get('logging.path');
        if (!is_string($logPath) || $logPath === '') {
            $basePath = $container->get('path.base');
            $logPath = (is_string($basePath) ? $basePath : (string) getcwd()) . '/storage/logs/app.log';
        }
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
