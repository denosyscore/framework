<?php

declare(strict_types=1);

use Denosys\Config\ConfigurationInterface;
use Denosys\Container\Container;
use Denosys\Container\Exceptions\ContainerResolutionException;
use Denosys\Mail\MailServiceProvider;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport\SendmailTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;

function mailContainer(array $settings): Container
{
    $config = Mockery::mock(ConfigurationInterface::class);
    $config->shouldReceive('get')->andReturnUsing(static fn(string $key, mixed $default = null): mixed => $settings[$key] ?? $default);
    $container = new Container();
    $container->instance(ConfigurationInterface::class, $config);
    new MailServiceProvider()->register($container);

    return $container;
}

it('binds the standard mailer and a configured SMTP transport', function (): void {
    $container = mailContainer([
        'mail.default' => 'smtp',
        'mail.mailers.smtp.host' => 'mail.example.test',
        'mail.mailers.smtp.port' => 2525,
        'mail.mailers.smtp.encryption' => 'ssl',
    ]);

    expect($container->get(MailerInterface::class))->toBeInstanceOf(MailerInterface::class);
    expect((string) $container->get(TransportInterface::class))->toContain('smtps://mail.example.test:2525');
});

it('supports sendmail and explicit Symfony DSNs', function (): void {
    $sendmail = mailContainer([
        'mail.default' => 'sendmail',
        'mail.mailers.sendmail.path' => '/usr/sbin/sendmail -bs',
    ]);
    expect($sendmail->get(TransportInterface::class))->toBeInstanceOf(SendmailTransport::class);

    $dsn = mailContainer(['mail.dsn' => 'null://null', 'mail.default' => 'unsupported']);
    expect((string) $dsn->get(TransportInterface::class))->toBe('null://');
});

it('rejects unsupported drivers rather than discarding messages', function (): void {
    $container = mailContainer(['mail.default' => 'array']);

    expect(fn() => $container->get(MailerInterface::class))
        ->toThrow(ContainerResolutionException::class);
});
