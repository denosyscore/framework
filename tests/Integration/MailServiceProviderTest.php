<?php

declare(strict_types=1);

use Denosys\Config\ConfigurationInterface;
use Denosys\Container\Container;
use Denosys\Container\Exceptions\ContainerResolutionException;
use Denosys\Application;
use Denosys\Console\Application as ConsoleApplication;
use Denosys\Console\Command\MakeMailCommand;
use Denosys\Mail\MailServiceProvider;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
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

it('resolves the registered Symfony mailer through the global helper', function (): void {
    $previousApplication = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-mail-helper-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);

    try {
        $application = new Application($base);
        $container = mailContainer(['mail.dsn' => 'null://null']);
        $application->setContainer($container);

        expect(mailer())->toBe($container->get(MailerInterface::class));
    } finally {
        if ($previousApplication === null) {
            Application::resetInstance();
        } else {
            Application::setInstance($previousApplication);
        }
        rmdir($base . '/config');
        rmdir($base);
    }
});

it('sends a generated console mail message through the framework provider', function (): void {
    $base = sys_get_temp_dir() . '/denosys-framework-generated-mail-' . bin2hex(random_bytes(6));
    mkdir($base, 0700);
    $name = 'MailProbe' . bin2hex(random_bytes(4));

    try {
        $console = new ConsoleApplication(new Container());
        $console->add(new MakeMailCommand($base));
        $output = new BufferedOutput();
        expect($console->getSymfonyApplication()->run(new StringInput("make:mail {$name}"), $output))->toBe(0);

        require $base . "/app/Mail/{$name}.php";
        $messageClass = 'App\\Mail\\' . $name;
        $message = new $messageClass('sender@example.test', 'recipient@example.test', 'Hello');
        mailContainer(['mail.dsn' => 'null://null'])->get(MailerInterface::class)->send($message);

        expect($message->getTextBody())->toBe('Hello');
    } finally {
        $generated = $base . "/app/Mail/{$name}.php";
        if (is_file($generated)) {
            unlink($generated);
        }
        foreach ([$base . '/app/Mail', $base . '/app', $base] as $directory) {
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
});
