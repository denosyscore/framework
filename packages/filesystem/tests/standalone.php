<?php

declare(strict_types=1);

use Denosys\Contracts\Config\ConfigReader;
use Denosys\Filesystem\FilesystemInterface;
use Denosys\Filesystem\FilesystemManager;

require dirname(__DIR__) . '/vendor/autoload.php';

function check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

$config = new class implements ConfigReader {
    public function get(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'filesystems.default' => 'local',
            'filesystems.disks.local' => ['driver' => 'local'],
            'filesystems.disks.s3' => [
                'driver' => 's3',
                'key' => 'test-key',
                'secret' => 'test-secret',
                'region' => 'us-east-1',
                'bucket' => 'test-bucket',
            ],
            default => $default,
        };
    }
};

$composer = json_decode((string) file_get_contents(dirname(__DIR__) . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
check(!isset($composer['require']['league/flysystem-aws-s3-v3']), 'The S3 adapter must be optional, not installed for every application');
check(isset($composer['suggest']['league/flysystem-aws-s3-v3']), 'The S3 adapter must be suggested for applications that use S3 disks');

$directory = sys_get_temp_dir() . '/denosys-filesystem-' . bin2hex(random_bytes(6));
mkdir($directory, 0700);

try {
    $direct = new FilesystemManager($config, $directory . '/direct');
    check($direct->disk()->put('probe.txt', 'direct'), 'Direct manager write failed');
    check(file_get_contents($directory . '/direct/probe.txt') === 'direct', 'Direct manager used the wrong root');
    if (class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
        check($direct->disk('s3') instanceof FilesystemInterface, 'S3 adapter construction failed');
    } else {
        try {
            $direct->disk('s3');
            check(false, 'S3 disk resolved without its adapter installed');
        } catch (RuntimeException $exception) {
            check(str_contains($exception->getMessage(), 'composer require league/flysystem-aws-s3-v3'), 'Missing S3 adapter error does not say how to install it');
        }
    }

    echo "Standalone filesystem integration passed\n";
} finally {
    foreach (['direct/probe.txt', 'private/app/probe.txt', 'storage/app/probe.txt'] as $path) {
        if (is_file($directory . '/' . $path)) {
            unlink($directory . '/' . $path);
        }
    }
    foreach (['direct', 'private/app', 'private', 'storage/app', 'storage', ''] as $path) {
        $candidate = rtrim($directory . '/' . $path, '/');
        if (is_dir($candidate)) {
            rmdir($candidate);
        }
    }
}
