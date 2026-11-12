<?php

declare(strict_types=1);

namespace Denosys\Filesystem;

use League\Flysystem\FilesystemOperator;
use League\Flysystem\UnableToReadFile;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\StorageAttributes;
use Psr\Http\Message\UploadedFileInterface;
use Denosys\Filesystem\Exceptions\FileNotFoundException;
use Denosys\Filesystem\Exceptions\FileWriteException;

/**
 * Filesystem implementation wrapping Flysystem.
 */
class Filesystem implements FilesystemInterface
{
    public function __construct(
        private FilesystemOperator $driver,
        private string $root = '',
        private ?string $url = null
    ) {
    }

    public function exists(string $path): bool
    {
        return $this->driver->fileExists($path);
    }

    public function get(string $path): string
    {
        try {
            return $this->driver->read($path);
        } catch (UnableToReadFile $e) {
            throw new FileNotFoundException($path, $e);
        }
    }

    public function readStream(string $path)
    {
        try {
            return $this->driver->readStream($path);
        } catch (UnableToReadFile $e) {
            throw new FileNotFoundException($path, $e);
        }
    }

    public function put(string $path, string $contents, array $options = []): bool
    {
        try {
            $this->driver->write($path, $contents, $options);
            return true;
        } catch (UnableToWriteFile $e) {
            throw new FileWriteException($path, $e);
        }
    }

    public function putStream(string $path, $resource, array $options = []): bool
    {
        try {
            $this->driver->writeStream($path, $resource, $options);
            return true;
        } catch (UnableToWriteFile $e) {
            throw new FileWriteException($path, $e);
        }
    }

    public function putFile(string $path, UploadedFileInterface $file, array $options = []): string|false
    {
        $name = $this->generateFilename($file);
        return $this->putFileAs($path, $file, $name, $options);
    }

    public function putFileAs(string $path, UploadedFileInterface $file, string $name, array $options = []): string|false
    {
        $stream = $file->getStream()->detach();
        if ($stream === null) {
            return false;
        }

        $filePath = rtrim($path, '/') . '/' . ltrim($name, '/');

        try {
            // Rewind stream to beginning to ensure all content is written
            rewind($stream);
            
            $this->driver->writeStream($filePath, $stream, $options);
            return $filePath;
        } catch (UnableToWriteFile) {
            return false;
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    public function prepend(string $path, string $data): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $data . $this->get($path));
        }
        return $this->put($path, $data);
    }

    public function append(string $path, string $data): bool
    {
        if ($this->exists($path)) {
            return $this->put($path, $this->get($path) . $data);
        }
        return $this->put($path, $data);
    }

    /**
     * @param string|array<string> $paths
     */
    public function delete(string|array $paths): bool
    {
        $paths = is_array($paths) ? $paths : [$paths];

        foreach ($paths as $path) {
            try {
                $this->driver->delete($path);
            } catch (\Throwable) {
                return false;
            }
        }

        return true;
    }

    public function copy(string $from, string $to): bool
    {
        try {
            $this->driver->copy($from, $to);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function move(string $from, string $to): bool
    {
        try {
            $this->driver->move($from, $to);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function size(string $path): int
    {
        return $this->driver->fileSize($path);
    }

    public function lastModified(string $path): int
    {
        return $this->driver->lastModified($path);
    }

    public function mimeType(string $path): string|false
    {
        try {
            return $this->driver->mimeType($path);
        } catch (\Throwable) {
            return false;
        }
    }

    public function files(string $directory = '', bool $recursive = false): array
    {
        $listing = $recursive
            ? $this->driver->listContents($directory, true)
            : $this->driver->listContents($directory);

        return array_values(array_filter(
            array_map(
                fn(StorageAttributes $attr) => $attr->isFile() ? $attr->path() : null,
                iterator_to_array($listing)
            )
        ));
    }

    public function directories(string $directory = '', bool $recursive = false): array
    {
        $listing = $recursive
            ? $this->driver->listContents($directory, true)
            : $this->driver->listContents($directory);

        return array_values(array_filter(
            array_map(
                fn(StorageAttributes $attr) => $attr->isDir() ? $attr->path() : null,
                iterator_to_array($listing)
            )
        ));
    }

    public function makeDirectory(string $path): bool
    {
        try {
            $this->driver->createDirectory($path);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteDirectory(string $directory): bool
    {
        try {
            $this->driver->deleteDirectory($directory);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function url(string $path): string
    {
        if ($this->url !== null) {
            return rtrim($this->url, '/') . '/' . ltrim($path, '/');
        }

        return $path;
    }

    public function temporaryUrl(string $path, \DateTimeInterface $expiration): string
    {
        // Override in adapter-specific implementations (e.g., S3)
        return $this->url($path);
    }

    public function path(string $path): string
    {
        return $this->root !== ''
            ? rtrim($this->root, '/') . '/' . ltrim($path, '/')
            : $path;
    }

    /**
     * Generate a unique filename for an uploaded file.
     */
    private function generateFilename(UploadedFileInterface $file): string
    {
        $extension = pathinfo($file->getClientFilename() ?? '', PATHINFO_EXTENSION);
        $name = bin2hex(random_bytes(20));
        
        return $extension !== '' ? "{$name}.{$extension}" : $name;
    }
}
