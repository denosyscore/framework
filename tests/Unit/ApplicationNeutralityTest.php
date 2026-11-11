<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use Denosys\Application;

function withNeutralApplication(callable $assertions): void
{
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-framework-neutral-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);

    try {
        $assertions(new Application($base), $base);
    } finally {
        $previous === null ? Application::resetInstance() : Application::setInstance($previous);
        rmdir($base . '/config');
        rmdir($base);
    }
}

it('ships no application-specific helpers', function (string $helper): void {
    expect(function_exists($helper))->toBeFalse();
})->with(['send_email', 'send_email_with_template', 'render_template', 'format_currency', 'rand_float', 'render_flash']);

it('does not install vendor services that applications have not asked for', function (string $package): void {
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require'])->not->toHaveKey($package);
})->with(['mailgun/mailgun-php', 'geoip2/geoip2', 'html2text/html2text', 'league/flysystem-aws-s3-v3', 'symfony/http-client']);

it('looks for views where applications keep them', function (): void {
    withNeutralApplication(function (Application $app, string $base): void {
        expect(Application::getContainer()->get('path.view'))->toBe($base . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views');
    });
});

it('reports the installed framework version', function (): void {
    withNeutralApplication(function (Application $app): void {
        expect($app->version())->toBe(InstalledVersions::getPrettyVersion('denosyscore/framework'));
    });
});

it('has a single console detection method', function (): void {
    expect(method_exists(Application::class, 'isConsole'))->toBeFalse()
        ->and(method_exists(Application::class, 'runningInConsole'))->toBeTrue();
});
