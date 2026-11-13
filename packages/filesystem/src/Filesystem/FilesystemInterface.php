<?php

declare(strict_types=1);

namespace Denosys\Filesystem;

use DateTimeInterface;
use Denosys\Filesystem\Exceptions\FileNotFoundException;
use Denosys\Filesystem\Exceptions\FilesystemException;
use Denosys\Filesystem\Exceptions\UnsafeFileType;
use Denosys\Filesystem\Exceptions\UnsupportedOperation;
use Psr\Http\Message\UploadedFileInterface;

/**
 * One disk. Every operation that cannot complete throws a
 * FilesystemException, with the adapter's error as getPrevious().
 */
interface FilesystemInterface
{
    /** @throws FilesystemException */
    public function exists(string $path): bool;

    /** @throws FileNotFoundException */
    public function get(string $path): string;

    /**
     * @return resource
     *
     * @throws FileNotFoundException
     */
    public function readStream(string $path);

    /**
     * @param array<string, mixed> $options Flysystem write options, such as visibility.
     *
     * @throws FilesystemException
     */
    public function put(string $path, string $contents, array $options = []): void;

    /**
     * @param resource $resource
     * @param array<string, mixed> $options
     *
     * @throws FilesystemException
     */
    public function putStream(string $path, $resource, array $options = []): void;

    /**
     * Stores an upload under $path with a random name whose extension comes
     * from the detected content type, never from the client's file name.
     *
     * @param array<string, mixed> $options
     * @return string the stored path
     *
     * @throws UnsafeFileType when the content is a type a web server could run or render
     * @throws FilesystemException
     */
    public function putFile(string $path, UploadedFileInterface $file, array $options = []): string;

    /**
     * Stores an upload under $path with the given name.
     *
     * @param array<string, mixed> $options
     * @return string the stored path
     *
     * @throws UnsafeFileType when the name or the content is a type a web server could run or render
     * @throws FilesystemException
     */
    public function putFileAs(string $path, UploadedFileInterface $file, string $name, array $options = []): string;

    /** @throws FilesystemException */
    public function prepend(string $path, string $data): void;

    /** @throws FilesystemException */
    public function append(string $path, string $data): void;

    /**
     * Deletes files; a file that is already gone is not an error.
     *
     * @param string|list<string> $paths
     *
     * @throws FilesystemException
     */
    public function delete(string|array $paths): void;

    /** @throws FilesystemException */
    public function copy(string $from, string $to): void;

    /** @throws FilesystemException */
    public function move(string $from, string $to): void;

    /** @throws FilesystemException */
    public function size(string $path): int;

    /** @throws FilesystemException */
    public function lastModified(string $path): int;

    /** @throws FilesystemException */
    public function mimeType(string $path): string;

    /**
     * @return list<string>
     *
     * @throws FilesystemException
     */
    public function files(string $directory = '', bool $recursive = false): array;

    /**
     * @return list<string>
     *
     * @throws FilesystemException
     */
    public function directories(string $directory = '', bool $recursive = false): array;

    /** @throws FilesystemException */
    public function makeDirectory(string $path): void;

    /** @throws FilesystemException */
    public function deleteDirectory(string $directory): void;

    /** The public URL of a file on a disk with a `url`. */
    public function url(string $path): string;

    /**
     * A signed URL that stops working at $expiration.
     *
     * @throws UnsupportedOperation when the disk cannot sign URLs
     */
    public function temporaryUrl(string $path, DateTimeInterface $expiration): string;

    /** The path on a local disk. */
    public function path(string $path): string;
}
