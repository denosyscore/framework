<?php

declare(strict_types=1);

namespace Denosys\Filesystem;

use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;
use League\Flysystem\Visibility;
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
        
        // Private files and directories are readable only by their owner unless
        // the disk's `permissions` say otherwise.
        $permissions = is_array($config['permissions'] ?? null) ? $config['permissions'] : [];
        $fileVisibility = is_string($config['visibility'] ?? null) ? $config['visibility'] : Visibility::PRIVATE;
        $directoryVisibility = is_string($config['directory_visibility'] ?? null) ? $config['directory_visibility'] : $fileVisibility;
        $visibility = PortableVisibilityConverter::fromArray([
            'file' => ['public' => 0644, 'private' => 0600, ...(is_array($permissions['file'] ?? null) ? $permissions['file'] : [])],
            'dir' => ['public' => 0755, 'private' => 0700, ...(is_array($permissions['dir'] ?? null) ? $permissions['dir'] : [])],
        ], $directoryVisibility);
        $adapter = new LocalFilesystemAdapter($root, $visibility);

        // The wrapper passes these with every write, so they apply whatever
        // version of Flysystem merges disk defaults.
        return new Filesystem(new FlysystemFilesystem($adapter), $root, $url, [
            'visibility' => $fileVisibility,
            'directory_visibility' => $directoryVisibility,
        ]);
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

        $client = new \Aws\S3\S3Client(self::s3ClientOptions($config));

        $bucket = $config['bucket'] ?? '';
        $prefix = $config['prefix'] ?? '';

        $adapter = new \League\Flysystem\AwsS3V3\AwsS3V3Adapter($client, $bucket, $prefix);
        $flysystem = new FlysystemFilesystem($adapter);

        $url = $config['url'] ?? "https://{$bucket}.s3.amazonaws.com";

        return new Filesystem($flysystem, '', $url);
    }

    /**
     * The S3 client options for a disk: credentials and region, `endpoint`
     * and `use_path_style_endpoint` for S3-compatible stores, and any client
     * options under `options`.
     *
     * @internal
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    public static function s3ClientOptions(array $config): array
    {
        $options = [
            'credentials' => [
                'key' => $config['key'] ?? '',
                'secret' => $config['secret'] ?? '',
            ],
            'region' => $config['region'] ?? 'us-east-1',
            'version' => 'latest',
        ];
        if (isset($config['endpoint'])) {
            $options['endpoint'] = $config['endpoint'];
        }
        if (isset($config['use_path_style_endpoint'])) {
            $options['use_path_style_endpoint'] = (bool) $config['use_path_style_endpoint'];
        }

        foreach (is_array($config['options'] ?? null) ? $config['options'] : [] as $name => $value) {
            if (is_string($name)) {
                $options[$name] = $value;
            }
        }

        return $options;
    }
}
