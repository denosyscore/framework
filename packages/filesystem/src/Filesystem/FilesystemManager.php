<?php

declare(strict_types=1);

namespace Denosys\Filesystem;

use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use Denosys\Contracts\Config\ConfigReader;
use Denosys\Filesystem\Exceptions\InvalidDiskException;

/**
 * Filesystem manager for handling multiple disks.
 */
class FilesystemManager
{
    /** @var array<string, FilesystemInterface> */
    private array $disks = [];

    public function __construct(
        private ConfigReader $config,
        private ?string $defaultLocalRoot = null
    ) {
    }

    /**
     * Get a filesystem disk instance.
     */
    public function disk(?string $name = null): FilesystemInterface
    {
        $name ??= $this->getDefaultDisk();

        return $this->disks[$name] ??= $this->resolve($name);
    }

    /**
     * Get the default disk name.
     */
    public function getDefaultDisk(): string
    {
        return (string) $this->config->get('filesystems.default', 'local');
    }

    /**
     * Resolve a disk by name.
     */
    protected function resolve(string $name): FilesystemInterface
    {
        $config = $this->getDiskConfig($name);

        if ($config === null) {
            throw new InvalidDiskException($name);
        }

        $driver = $config['driver'] ?? 'local';

        return match ($driver) {
            'local' => $this->createLocalDriver($config),
            's3' => $this->createS3Driver($config),
            default => throw new InvalidDiskException($name),
        };
    }

    /**
     * Get the disk configuration.
     *
     * @return array<string, mixed>|null
     */
    protected function getDiskConfig(string $name): ?array
    {
        $config = $this->config->get("filesystems.disks.{$name}");
        return is_array($config) ? $config : null;
    }

    /**
     * Create a local filesystem driver.
     *
     * @param array<string, mixed> $config
     */
    protected function createLocalDriver(array $config): FilesystemInterface
    {
        $root = $config['root'] ?? $this->defaultLocalRoot ?? getcwd() . '/storage/app';
        $url = $config['url'] ?? null;
        
        $adapter = new LocalFilesystemAdapter($root);
        $flysystem = new FlysystemFilesystem($adapter);

        return new Filesystem($flysystem, $root, $url);
    }

    /**
     * Create an S3 filesystem driver.
     *
     * @param array<string, mixed> $config
     */
    protected function createS3Driver(array $config): FilesystemInterface
    {
        // Requires league/flysystem-aws-s3-v3
        if (!class_exists(\League\Flysystem\AwsS3V3\AwsS3V3Adapter::class)) {
            throw new \RuntimeException(
                'S3 driver requires league/flysystem-aws-s3-v3. Install with: composer require league/flysystem-aws-s3-v3'
            );
        }

        $client = new \Aws\S3\S3Client([
            'credentials' => [
                'key' => $config['key'] ?? '',
                'secret' => $config['secret'] ?? '',
            ],
            'region' => $config['region'] ?? 'us-east-1',
            'version' => 'latest',
        ]);

        $bucket = $config['bucket'] ?? '';
        $prefix = $config['prefix'] ?? '';

        $adapter = new \League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $bucket, $prefix);
        $flysystem = new FlysystemFilesystem($adapter);

        $url = $config['url'] ?? "https://{$bucket}.s3.amazonaws.com";

        return new Filesystem($flysystem, '', $url);
    }

    /**
     * Dynamically call the default disk instance.
     *
     * @param array<mixed> $parameters
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->disk()->$method(...$parameters);
    }
}
