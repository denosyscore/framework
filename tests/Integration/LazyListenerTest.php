<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Container\ContainerInterface;
use Denosys\Events\Dispatcher;
use Denosys\Framework\Provider;
use Psr\EventDispatcher\EventDispatcherInterface;

final class UserRegistered
{
    public ?string $welcomedBy = null;
}

final class WelcomeMailer
{
    public string $name = 'mailer';
}

final class SendWelcomeMail
{
    public static int $built = 0;

    public function __construct(private WelcomeMailer $mailer)
    {
        self::$built++;
    }

    public function __invoke(UserRegistered $event): void
    {
        $event->welcomedBy = $this->mailer->name;
    }
}

final class ListensForRegistrations implements Provider
{
    public function register(ContainerInterface $container): void
    {
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        $events = $container->get(Dispatcher::class);
        if ($events instanceof Dispatcher) {
            $events->listen(UserRegistered::class, SendWelcomeMail::class);
        }
    }
}

it('builds class listeners with their dependencies only when the event fires', function (): void {
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-listeners-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    SendWelcomeMail::$built = 0;
    $app = Application::configure(basePath: $base)->withProviders([ListensForRegistrations::class])->create();

    try {
        $app->boot();
        expect(SendWelcomeMail::$built)->toBe(0);

        $event = $app->getContainer()->get(EventDispatcherInterface::class)->dispatch(new UserRegistered());

        expect(SendWelcomeMail::$built)->toBe(1)
            ->and($event->welcomedBy)->toBe('mailer');
    } finally {
        restore_error_handler();
        restore_exception_handler();
        $previous === null ? Application::resetInstance() : Application::setInstance($previous);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($base);
    }
});
