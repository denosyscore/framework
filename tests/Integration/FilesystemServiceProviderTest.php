<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Config\Repository;
use Denosys\Container\Container;
use Denosys\Contracts\Config\ConfigReader;
use Denosys\Filesystem\FilesystemInterface;
use Denosys\Framework\Providers\FilesystemServiceProvider;

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

        expect($container->get(ConfigReader::class))
            ->toBe($container->get(Repository::class));

        (new FilesystemServiceProvider())->register($container);
        $disk = $container->get(FilesystemInterface::class);
        $disk->put('probe.txt', 'private');
        expect(file_get_contents($base . '/storage/app/probe.txt'))->toBe('private');
    });
});

it('resolves the current configuration after its framework binding is replaced', function (): void {
    withFilesystemApplication(function (): void {
        $container = Application::getContainer();
        $replacement = new Repository();
        $container->instance(Repository::class, $replacement);
        expect($container->get(ConfigReader::class))->toBe($replacement);
    });
});

it('binds the filesystem reader when a prepared container replaces the application container', function (): void {
    withFilesystemApplication(function (Application $app, string $base): void {
        $config = Application::getContainer()->get(Repository::class);
        $replacement = new Container();
        $replacement->instance(Repository::class, $config);
        $replacement->instance('path.storage', $base . '/storage');
        $app->setContainer($replacement);

        (new FilesystemServiceProvider())->register($replacement);
        expect($replacement->get(ConfigReader::class))->toBe($config);
        $replacement->get(FilesystemInterface::class)->put('probe.txt', 'replaced');
        expect(file_get_contents($base . '/storage/app/probe.txt'))->toBe('replaced');
    });
});

it('falls back to storage/app under the working directory when no storage path is bound', function (): void {
    $directory = sys_get_temp_dir() . '/denosys-framework-filesystem-cwd-' . bin2hex(random_bytes(6));
    mkdir($directory, 0700, true);
    $previous = getcwd();
    chdir($directory);

    try {
        $container = new Container();
        $container->instance(ConfigReader::class, localDiskConfig());
        (new FilesystemServiceProvider())->register($container);

        $container->get(FilesystemInterface::class)->put('probe.txt', 'fallback');
        expect(file_get_contents($directory . '/storage/app/probe.txt'))->toBe('fallback');
    } finally {
        chdir((string) $previous);
        @unlink($directory . '/storage/app/probe.txt');
        @rmdir($directory . '/storage/app');
        @rmdir($directory . '/storage');
        @rmdir($directory);
    }
});

it('rejects an empty storage path', function (): void {
    $container = new Container();
    $container->instance(ConfigReader::class, localDiskConfig());
    $container->instance('path.storage', '');
    (new FilesystemServiceProvider())->register($container);

    try {
        $container->get(Denosys\Filesystem\FilesystemManager::class);
        throw new RuntimeException('An empty storage path was accepted.');
    } catch (Denosys\Container\Exceptions\ContainerResolutionException $exception) {
        expect($exception->getPrevious())->toBeInstanceOf(InvalidArgumentException::class)
            ->and($exception->getPrevious()?->getMessage())->toBe('path.storage must be a non-empty string');
    }
});

function localDiskConfig(): ConfigReader
{
    return new class implements ConfigReader {
        public function get(string $key, mixed $default = null): mixed
        {
            return ['filesystems.default' => 'local', 'filesystems.disks.local' => ['driver' => 'local']][$key] ?? $default;
        }
    };
}
