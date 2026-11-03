<?php

declare(strict_types=1);

namespace Denosys\Mail;

use Denosys\Config\ConfigurationInterface;
use Denosys\Container\ContainerInterface;
use Denosys\Contracts\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class MailServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $container->singleton(TransportInterface::class, static function (ContainerInterface $container): TransportInterface {
            return (new MailTransportFactory())->create($container->get(ConfigurationInterface::class));
        });

        $container->singleton(MailerInterface::class, static function (ContainerInterface $container): MailerInterface {
            return new Mailer($container->get(TransportInterface::class));
        });
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
    }
}
