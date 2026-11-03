<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Config\Configuration;
use Denosys\Config\ConfigurationInterface;
use Denosys\Container\Container;
use Denosys\Contracts\Config\ConfigurationReaderInterface;
use Denosys\Filesystem\FilesystemInterface;
use Denosys\Filesystem\FilesystemServiceProvider;

function withFilesystemApplication(callable $assertions): void
{
    $previousApplication = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-framework-filesystem-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    file_put_contents($base . '/config/filesystems.php', "<?php return ['default' => 'local', 'disks' => ['local' => ['driver' => 'local']]];\n");

    try {
        $assertions(new Application($base), $base);
    } finally {
        if ($previousApplication === null) {
            Application::resetInstance();
        } else {
            Application::setInstance($previousApplication);
        }
        if (is_file($base . '/storage/app/probe.txt')) {
            unlink($base . '/storage/app/probe.txt');
        }
        unlink($base . '/config/filesystems.php');
        foreach (['storage/app', 'storage', 'config', ''] as $path) {
            $directory = rtrim($base . '/' . $path, '/');
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}

it('binds framework configuration for the filesystem provider and keeps local files private', function (): void {
    withFilesystemApplication(function (Application $app, string $base): void {
        $container = Application::getContainer();

        expect($container->get(ConfigurationReaderInterface::class))
            ->toBe($container->get(ConfigurationInterface::class));

        (new FilesystemServiceProvider())->register($container);
        $disk = $container->get(FilesystemInterface::class);
        expect($disk->put('probe.txt', 'private'))->toBeTrue();
        expect(file_get_contents($base . '/storage/app/probe.txt'))->toBe('private');
    });
});

it('resolves the current configuration after its framework binding is replaced', function (): void {
    withFilesystemApplication(function (): void {
        $container = Application::getContainer();
        $replacement = new Configuration();
        $container->instance(ConfigurationInterface::class, $replacement);
        expect($container->get(ConfigurationReaderInterface::class))->toBe($replacement);
    });
});

it('binds the filesystem reader when a prepared container replaces the application container', function (): void {
    withFilesystemApplication(function (Application $app, string $base): void {
        $config = Application::getContainer()->get(ConfigurationInterface::class);
        $replacement = new Container();
        $replacement->instance(ConfigurationInterface::class, $config);
        $replacement->instance('path.storage', $base . '/storage');
        $app->setContainer($replacement);

        (new FilesystemServiceProvider())->register($replacement);
        expect($replacement->get(ConfigurationReaderInterface::class))->toBe($config);
        expect($replacement->get(FilesystemInterface::class)->put('probe.txt', 'replaced'))->toBeTrue();
        expect(file_get_contents($base . '/storage/app/probe.txt'))->toBe('replaced');
    });
});
