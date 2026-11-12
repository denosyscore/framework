<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Config\Repository;
use Denosys\Container\Container;
use Denosys\Framework\Providers\LoggerServiceProvider;
use Psr\Log\LoggerInterface;

function withConfiguredApplication(array $files, string $dotenv, callable $assertions): void
{
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-env-reader-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    file_put_contents($base . '/.env', $dotenv);
    foreach ($files as $name => $values) {
        file_put_contents("{$base}/config/{$name}.php", '<?php return ' . $values . ';');
    }

    try {
        $assertions(new Application($base), $base);
    } finally {
        $previous === null ? Application::resetInstance() : Application::setInstance($previous);
        foreach (array_reverse(glob($base . '/{,*/,*/*/}*', GLOB_BRACE) ?: []) as $path) {
            is_dir($path) ? @rmdir($path) : @unlink($path);
        }
        @unlink($base . '/.env');
        @rmdir($base);
    }
}

it('casts environment values the same way in config files and the env() helper', function (): void {
    $key = 'DENOSYS_READER_' . bin2hex(random_bytes(4));
    withConfiguredApplication(
        ['app' => "['port' => env('{$key}')]"],
        "{$key}=(int)8080\n",
        function (Application $app) use ($key): void {
            expect($app->getContainer()->get(Repository::class)->get('app.port'))->toBe(8080)
                ->and(env($key))->toBe(8080)
                ->and($app->getEnvironment()->get($key))->toBe(8080);
        },
    );
});

it('decides local mode from configuration, not the process environment', function (): void {
    withConfiguredApplication(['app' => "['env' => 'local']"], '', function (Application $app): void {
        expect($app->isLocal())->toBeTrue();
    });
    withConfiguredApplication(['app' => "['env' => 'production']"], '', function (Application $app): void {
        expect($app->isLocal())->toBeFalse();
    });
});

it('writes logs to the configured path', function (): void {
    $log = sys_get_temp_dir() . '/denosys-log-' . bin2hex(random_bytes(6)) . '.log';
    $container = new Container();
    $container->instance(Repository::class, new Repository(['app' => ['env' => 'local'], 'logging' => ['path' => $log]]));
    $container->instance('path.base', sys_get_temp_dir());

    (new LoggerServiceProvider())->register($container);
    $container->get(LoggerInterface::class)->debug('probe message');

    try {
        expect(file_get_contents($log))->toContain('probe message')->toContain('local.DEBUG');
    } finally {
        @unlink($log);
    }
});
