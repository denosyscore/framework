<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Config\ConfigurationInterface;
use Denosys\Contracts\Config\ConfigurationReaderInterface;
use Denosys\Filesystem\FilesystemInterface;
use Denosys\Filesystem\FilesystemServiceProvider;

it('binds framework configuration for the filesystem provider and keeps local files private', function (): void {
    $previousApplication = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-framework-filesystem-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    file_put_contents($base . '/config/filesystems.php', "<?php return ['default' => 'local', 'disks' => ['local' => ['driver' => 'local']]];\n");

    try {
        new Application($base);
        $container = Application::getContainer();

        expect($container->get(ConfigurationReaderInterface::class))
            ->toBe($container->get(ConfigurationInterface::class));

        (new FilesystemServiceProvider())->register($container);
        $disk = $container->get(FilesystemInterface::class);
        expect($disk->put('probe.txt', 'private'))->toBeTrue();
        expect(file_get_contents($base . '/storage/app/probe.txt'))->toBe('private');
    } finally {
        if ($previousApplication === null) {
            Application::resetInstance();
        } else {
            Application::setInstance($previousApplication);
        }
        if (is_file($base . '/storage/app/probe.txt')) {
            unlink($base . '/storage/app/probe.txt');
        }
        if (is_file($base . '/config/filesystems.php')) {
            unlink($base . '/config/filesystems.php');
        }
        foreach (['storage/app', 'storage', 'config', ''] as $path) {
            $directory = rtrim($base . '/' . $path, '/');
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
});
