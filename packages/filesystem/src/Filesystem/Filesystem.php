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
        'svg', 'svgz', 'js', 'mjs', 'htaccess', 'xml', 'xsl', 'xslt', 'xht', 'mht', 'mhtml',
    ];

    private FinfoMimeTypeDetector $mimeTypes;

    /**
     * @param array{visibility?: string, directory_visibility?: string} $defaults
     *        visibility for files and directories this disk creates
     */
    public function __construct(
        private readonly FilesystemOperator $driver,
        private readonly string $root = '',
        private readonly ?string $url = null,
        private readonly array $defaults = [],
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
            $this->driver->write($path, $contents, $options + $this->defaults);
        } catch (FlysystemException $e) {
            throw new FileWriteException($path, $e);
        }
    }

    public function putStream(string $path, $resource, array $options = []): void
    {
        try {
            $this->driver->writeStream($path, $resource, $options + $this->defaults);
        } catch (FlysystemException $e) {
            throw new FileWriteException($path, $e);
        }
    }

    public function putFile(string $path, UploadedFileInterface $file, array $options = []): string
    {
        $stream = $this->seekableStream($file);
        $extension = $this->safeExtension($stream);

        return $this->store($path, $stream, bin2hex(random_bytes(20)) . ($extension === null ? '' : '.' . $extension), $options);
    }

    public function putFileAs(string $path, UploadedFileInterface $file, string $name, array $options = []): string
    {
        self::assertSafeName($name);
        $stream = $this->seekableStream($file);
        $this->safeExtension($stream);

        return $this->store($path, $stream, $name, $options);
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
            $visibility = $this->defaults['directory_visibility'] ?? null;
            $this->driver->createDirectory($path, $visibility === null ? [] : ['visibility' => $visibility]);
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
     * The upload as a seekable stream, copying it to a temporary stream when
     * it cannot seek, so detection and storage both see every byte.
     *
     * @return resource
     *
     * @throws FileWriteException
     */
    private function seekableStream(UploadedFileInterface $file)
    {
        $upload = $file->getStream();
        $resource = $upload->detach();
        if ($resource === null) {
            throw new FileWriteException((string) $file->getClientFilename());
        }

        if (stream_get_meta_data($resource)['seekable']) {
            rewind($resource);

            return $resource;
        }

        $copy = fopen('php://temp', 'w+b');
        if ($copy === false || stream_copy_to_stream($resource, $copy) === false) {
            throw new FileWriteException((string) $file->getClientFilename());
        }
        fclose($resource);
        rewind($copy);

        return $copy;
    }

    /**
     * The extension of the stream's detected content type, or null when the
     * type has none. Refuses types a web server could run or render.
     *
     * @param resource $stream
     *
     * @throws UnsafeFileType
     */
    private function safeExtension($stream): ?string
    {
        $sample = (string) fread($stream, 8192);
        rewind($stream);

        $type = $this->mimeTypes->detectMimeTypeFromBuffer($sample) ?? 'application/octet-stream';
        $extension = $this->mimeTypes->lookupExtension($type);
        if ($extension !== null && in_array($extension, self::UNSAFE_EXTENSIONS, true)) {
            throw new UnsafeFileType($type);
        }
        if (preg_match('#^(text/(x-)?php|application/(x-)?(httpd-)?php|text/html|image/svg|application/(x-)?javascript|text/javascript|(text|application)/([\w.+-]+\+)?xml|application/xhtml)#', $type) === 1) {
            throw new UnsafeFileType($type);
        }

        return $extension;
    }

    /**
     * Refuses a name that a web server could run or treat as configuration:
     * any dotted segment with an unsafe extension (a.php.jpg), trailing dots
     * or spaces that some systems strip (a.php.), and .ht* or .user.ini.
     *
     * @throws UnsafeFileType
     */
    private static function assertSafeName(string $name): void
    {
        if (preg_match('#[/\\\\\0]#', $name) === 1) {
            throw new UnsafeFileType('a name with a path');
        }

        $base = strtolower(rtrim($name, ". \t"));
        if ($base === '' || str_starts_with($base, '.ht') || $base === '.user.ini') {
            throw new UnsafeFileType($base);
        }

        foreach (array_slice(explode('.', $base), 1) as $segment) {
            if (in_array(trim($segment), self::UNSAFE_EXTENSIONS, true)) {
                throw new UnsafeFileType(trim($segment));
            }
        }
    }

    /**
     * @param resource $stream
     * @param array<string, mixed> $options
     *
     * @throws FilesystemException
     */
    private function store(string $path, $stream, string $name, array $options): string
    {
        $target = ltrim(rtrim($path, '/') . '/' . $name, '/');
        try {
            $this->driver->writeStream($target, $stream, $options + $this->defaults);

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
