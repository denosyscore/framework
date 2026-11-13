<?php

declare(strict_types=1);

namespace Denosys\Filesystem;

use DateTimeInterface;
use Denosys\Filesystem\Exceptions\FileNotFoundException;
use Denosys\Filesystem\Exceptions\FileOperationFailed;
use Denosys\Filesystem\Exceptions\FileWriteException;
use Denosys\Filesystem\Exceptions\FilesystemException;
use Denosys\Filesystem\Exceptions\UnsafeFileType;
use Denosys\Filesystem\Exceptions\UnsupportedOperation;
use League\Flysystem\FilesystemException as FlysystemException;
use League\Flysystem\FilesystemOperator;
use League\Flysystem\StorageAttributes;
use League\Flysystem\UnableToGenerateTemporaryUrl;
use League\MimeTypeDetection\FinfoMimeTypeDetector;
use Psr\Http\Message\UploadedFileInterface;

final class Filesystem implements FilesystemInterface
{
    /** Extensions a web server may run or render as a page; never stored. */
    private const UNSAFE_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'phps', 'pht',
        'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'html', 'htm', 'xhtml', 'shtml',
        'svg', 'svgz', 'js', 'mjs', 'htaccess',
    ];

    private FinfoMimeTypeDetector $mimeTypes;

    public function __construct(
        private readonly FilesystemOperator $driver,
        private readonly string $root = '',
        private readonly ?string $url = null,
    ) {
        $this->mimeTypes = new FinfoMimeTypeDetector();
    }

    public function exists(string $path): bool
    {
        try {
            return $this->driver->fileExists($path);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('check', $path, $e);
        }
    }

    public function get(string $path): string
    {
        try {
            return $this->driver->read($path);
        } catch (FlysystemException $e) {
            throw new FileNotFoundException($path, $e);
        }
    }

    public function readStream(string $path)
    {
        try {
            return $this->driver->readStream($path);
        } catch (FlysystemException $e) {
            throw new FileNotFoundException($path, $e);
        }
    }

    public function put(string $path, string $contents, array $options = []): void
    {
        try {
            $this->driver->write($path, $contents, $options);
        } catch (FlysystemException $e) {
            throw new FileWriteException($path, $e);
        }
    }

    public function putStream(string $path, $resource, array $options = []): void
    {
        try {
            $this->driver->writeStream($path, $resource, $options);
        } catch (FlysystemException $e) {
            throw new FileWriteException($path, $e);
        }
    }

    public function putFile(string $path, UploadedFileInterface $file, array $options = []): string
    {
        $extension = $this->safeExtension($file);

        return $this->store($path, $file, bin2hex(random_bytes(20)) . ($extension === null ? '' : '.' . $extension), $options);
    }

    public function putFileAs(string $path, UploadedFileInterface $file, string $name, array $options = []): string
    {
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($extension, self::UNSAFE_EXTENSIONS, true) || str_starts_with(strtolower(basename($name)), '.ht')) {
            throw new UnsafeFileType($extension === '' ? basename($name) : $extension);
        }
        $this->safeExtension($file);

        return $this->store($path, $file, $name, $options);
    }

    public function prepend(string $path, string $data): void
    {
        $this->put($path, $this->exists($path) ? $data . $this->get($path) : $data);
    }

    public function append(string $path, string $data): void
    {
        $this->put($path, $this->exists($path) ? $this->get($path) . $data : $data);
    }

    public function delete(string|array $paths): void
    {
        foreach (is_array($paths) ? $paths : [$paths] as $path) {
            try {
                $this->driver->delete($path);
            } catch (FlysystemException $e) {
                throw new FileOperationFailed('delete', $path, $e);
            }
        }
    }

    public function copy(string $from, string $to): void
    {
        try {
            $this->driver->copy($from, $to);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('copy', "{$from} to {$to}", $e);
        }
    }

    public function move(string $from, string $to): void
    {
        try {
            $this->driver->move($from, $to);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('move', "{$from} to {$to}", $e);
        }
    }

    public function size(string $path): int
    {
        try {
            return $this->driver->fileSize($path);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('read the size of', $path, $e);
        }
    }

    public function lastModified(string $path): int
    {
        try {
            return $this->driver->lastModified($path);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('read the modification time of', $path, $e);
        }
    }

    public function mimeType(string $path): string
    {
        try {
            return $this->driver->mimeType($path);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('detect the type of', $path, $e);
        }
    }

    public function files(string $directory = '', bool $recursive = false): array
    {
        return $this->listing($directory, $recursive, static fn (StorageAttributes $item): bool => $item->isFile());
    }

    public function directories(string $directory = '', bool $recursive = false): array
    {
        return $this->listing($directory, $recursive, static fn (StorageAttributes $item): bool => $item->isDir());
    }

    public function makeDirectory(string $path): void
    {
        try {
            $this->driver->createDirectory($path);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('create the directory', $path, $e);
        }
    }

    public function deleteDirectory(string $directory): void
    {
        try {
            $this->driver->deleteDirectory($directory);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('delete the directory', $directory, $e);
        }
    }

    public function url(string $path): string
    {
        return $this->url !== null ? rtrim($this->url, '/') . '/' . ltrim($path, '/') : $path;
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiration): string
    {
        try {
            return $this->driver->temporaryUrl($path, $expiration);
        } catch (UnableToGenerateTemporaryUrl $e) {
            throw new UnsupportedOperation("This disk cannot make a temporary URL for {$path}.", $e);
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('make a temporary URL for', $path, $e);
        }
    }

    public function path(string $path): string
    {
        return $this->root !== '' ? rtrim($this->root, '/') . '/' . ltrim($path, '/') : $path;
    }

    /**
     * The extension of the upload's detected content type, or null when the
     * type has none. Refuses types a web server could run or render.
     *
     * @throws UnsafeFileType
     */
    private function safeExtension(UploadedFileInterface $file): ?string
    {
        $stream = $file->getStream();
        if ($stream->isSeekable()) {
            $stream->rewind();
        }
        $sample = $stream->read(8192);
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        $type = $this->mimeTypes->detectMimeTypeFromBuffer($sample) ?? 'application/octet-stream';
        $extension = $this->mimeTypes->lookupExtension($type);
        if ($extension !== null && in_array($extension, self::UNSAFE_EXTENSIONS, true)) {
            throw new UnsafeFileType($type);
        }
        if (preg_match('#^(text/(x-)?php|application/(x-)?(httpd-)?php|text/html|image/svg|application/(x-)?javascript|text/javascript)#', $type) === 1) {
            throw new UnsafeFileType($type);
        }

        return $extension;
    }

    /**
     * @param array<string, mixed> $options
     *
     * @throws FilesystemException
     */
    private function store(string $path, UploadedFileInterface $file, string $name, array $options): string
    {
        $stream = $file->getStream()->detach();
        if ($stream === null) {
            throw new FileWriteException($name);
        }

        $target = ltrim(rtrim($path, '/') . '/' . ltrim($name, '/'), '/');
        try {
            rewind($stream);
            $this->driver->writeStream($target, $stream, $options);

            return $target;
        } catch (FlysystemException $e) {
            throw new FileWriteException($target, $e);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @param callable(StorageAttributes): bool $keep
     * @return list<string>
     *
     * @throws FilesystemException
     */
    private function listing(string $directory, bool $recursive, callable $keep): array
    {
        try {
            $paths = [];
            foreach ($this->driver->listContents($directory, $recursive) as $item) {
                if ($keep($item)) {
                    $paths[] = $item->path();
                }
            }

            return $paths;
        } catch (FlysystemException $e) {
            throw new FileOperationFailed('list', $directory, $e);
        }
    }
}
