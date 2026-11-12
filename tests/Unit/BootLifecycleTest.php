<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Container\ContainerInterface;
use Denosys\Framework\Provider;
use Denosys\Framework\Providers\HashingServiceProvider;
use Denosys\Framework\Providers\MailServiceProvider;
use Denosys\Framework\Providers\QueueServiceProvider;
use Denosys\Framework\Providers\FilesystemServiceProvider;
use Denosys\Framework\Providers\ViewServiceProvider;
use Laminas\Diactoros\ServerRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class CountingProvider implements Provider
{
    public static int $registered = 0;
    public static int $booted = 0;

    public function register(ContainerInterface $container): void
    {
        self::$registered++;
    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        self::$booted++;
    }
}

function withBootableApplication(callable $configure, callable $assertions): void
{
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-boot-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    file_put_contents($base . '/config/app.php', "<?php return ['env' => 'testing', 'debug' => false];");
    CountingProvider::$registered = 0;
    CountingProvider::$booted = 0;

    try {
        $application = $configure(Application::configure(basePath: $base))->create();
        $assertions($application);
    } finally {
        // Booting installs the application's error and exception handlers.
        if (isset($application) && $application->getContainer()->has('exception.handler')) {
            restore_error_handler();
            restore_exception_handler();
        }
        $previous === null ? Application::resetInstance() : Application::setInstance($previous);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($base);
    }
}

it('creates an application that is not booted until asked', function (): void {
    withBootableApplication(static fn (Application $app): Application => $app, function (Application $app): void {
        expect($app->getContainer()->has(LoggerInterface::class))->toBeFalse();

        $app->boot();

        expect($app->getContainer()->get(LoggerInterface::class))->toBeInstanceOf(LoggerInterface::class);
    });
});

it('boots once, with one event dispatcher shared by every entry point', function (): void {
    withBootableApplication(
        static fn (Application $app): Application => $app->withProviders([CountingProvider::class]),
        function (Application $app): void {
            $app->boot();
            $dispatcher = $app->getContainer()->get(EventDispatcherInterface::class);
            $app->boot();
            $app->handleRequest(new ServerRequest([], [], 'https://example.test/missing', 'GET'));

            expect(CountingProvider::$registered)->toBe(1)
                ->and(CountingProvider::$booted)->toBe(1)
                ->and($app->getContainer()->get(EventDispatcherInterface::class))->toBe($dispatcher);
        },
    );
});

it('includes the documented default providers and lets applications drop one', function (): void {
    expect(Application::defaultProviders())->toContain(
        MailServiceProvider::class,
        FilesystemServiceProvider::class,
        QueueServiceProvider::class,
        HashingServiceProvider::class,
        ViewServiceProvider::class,
    );

    withBootableApplication(
        static fn (Application $app): Application => $app->without(ViewServiceProvider::class),
        function (Application $app): void {
            expect($app->providers())->not->toContain(ViewServiceProvider::class)
                ->and($app->providers())->toContain(MailServiceProvider::class);
        },
    );
});

it('registers a provider listed twice only once', function (): void {
    withBootableApplication(
        static fn (Application $app): Application => $app->withProviders([CountingProvider::class, CountingProvider::class, MailServiceProvider::class]),
        function (Application $app): void {
            $app->boot();

            expect(CountingProvider::$registered)->toBe(1)
                ->and(array_count_values($app->providers())[MailServiceProvider::class])->toBe(1);
        },
    );
});

it('fails loudly on a provider class that does not exist', function (): void {
    withBootableApplication(
        static fn (Application $app): Application => $app->withProviders(['App\\Providers\\Misspelled']),
        function (Application $app): void {
            expect(fn () => $app->boot())->toThrow(InvalidArgumentException::class, 'App\\Providers\\Misspelled');
        },
    );
});

it('refuses to ignore providers left in bootstrap/providers.php', function (): void {
    withBootableApplication(
        static function (Application $app): Application {
            mkdir($app->basePath('bootstrap'), 0700, true);
            file_put_contents($app->basePath('bootstrap/providers.php'), "<?php return ['App\\\\Providers\\\\EventsProvider'];");

            return $app;
        },
        function (Application $app): void {
            expect(fn () => $app->boot())->toThrow(LogicException::class, 'withProviders()');
        },
    );
});

it('accepts an empty bootstrap/providers.php', function (): void {
    withBootableApplication(
        static function (Application $app): Application {
            mkdir($app->basePath('bootstrap'), 0700, true);
            file_put_contents($app->basePath('bootstrap/providers.php'), '<?php return [];');

            return $app;
        },
        function (Application $app): void {
            $app->boot();
            expect($app->getContainer()->has('exception.handler'))->toBeTrue();
        },
    );
});

it('keeps reporting a failed boot instead of treating the application as booted', function (): void {
    withBootableApplication(
        static fn (Application $app): Application => $app->withProviders(['App\\Providers\\Misspelled']),
        function (Application $app): void {
            expect(fn () => $app->boot())->toThrow(InvalidArgumentException::class)
                ->and(fn () => $app->boot())->toThrow(InvalidArgumentException::class, 'App\\Providers\\Misspelled')
                ->and(fn () => $app->handleRequest(new ServerRequest([], [], 'https://example.test/', 'GET')))
                    ->toThrow(InvalidArgumentException::class);
        },
    );
});

it('rejects excluding a provider that is not a default', function (): void {
    withBootableApplication(static fn (Application $app): Application => $app, function (Application $app): void {
        expect(fn () => $app->without('Denosys\\Framework\\Providers\\ViewServiceProvidre'))
            ->toThrow(InvalidArgumentException::class, 'ViewServiceProvidre');
    });
});

it('treats differently cased names of one provider class as one provider', function (): void {
    withBootableApplication(
        static fn (Application $app): Application => $app->withProviders([CountingProvider::class, strtolower(CountingProvider::class)]),
        function (Application $app): void {
            $app->boot();

            expect(CountingProvider::$registered)->toBe(1)
                ->and(array_count_values($app->providers())[CountingProvider::class])->toBe(1);
        },
    );
});

it('refuses to replace the container after boot', function (): void {
    withBootableApplication(static fn (Application $app): Application => $app, function (Application $app): void {
        $app->boot();

        expect(fn () => $app->setContainer(new Denosys\Container\Container()))->toThrow(LogicException::class, 'boot');
    });
});
