<?php

declare(strict_types=1);

namespace Denosys\Filesystem;

use Psr\Http\Message\UploadedFileInterface;

/**
 * Filesystem abstraction interface.
 * 
 * Provides a clean API for file operations across different storage backends.
 */
interface FilesystemInterface
{
    /**
     * Check if a file exists.
     */
    public function exists(string $path): bool;

    /**
     * Get the contents of a file.
     *
     * @throws Exceptions\FileNotFoundException
     */
    public function get(string $path): string;

    /**
     * Get a resource to read a file.
     *
     * @return resource
     * @throws Exceptions\FileNotFoundException
     */
    public function readStream(string $path);

    /**
     * Write contents to a file.
     *
     * @param array<string, mixed> $options
     * @throws Exceptions\FileWriteException
     */
    public function put(string $path, string $contents, array $options = []): bool;

    /**
     * Write a stream to a file.
     *
     * @param resource $resource
     * @param array<string, mixed> $options
     * @throws Exceptions\FileWriteException
     */
    public function putStream(string $path, $resource, array $options = []): bool;

    /**
     * Store an uploaded file.
     *
     * @param array<string, mixed> $options
     */
    public function putFile(string $path, UploadedFileInterface $file, array $options = []): string|false;

    /**
     * Store an uploaded file with a specific name.
     *
     * @param array<string, mixed> $options
     */
    public function putFileAs(string $path, UploadedFileInterface $file, string $name, array $options = []): string|false;

    /**
     * Prepend to a file.
     */
    public function prepend(string $path, string $data): bool;

    /**
     * Append to a file.
     */
    public function append(string $path, string $data): bool;

    /**
     * Delete a file.
     *
     * @param string|array<string> $paths
     */
    public function delete(string|array $paths): bool;

    /**
     * Copy a file.
     */
    public function copy(string $from, string $to): bool;

    /**
     * Move a file.
     */
    public function move(string $from, string $to): bool;

    /**
     * Get the file size.
     */
    public function size(string $path): int;

    /**
     * Get the file's last modification time.
     */
    public function lastModified(string $path): int;

    /**
     * Get the MIME type of a file.
     */
    public function mimeType(string $path): string|false;

    /**
     * Get all files in a directory.
     *
     * @return array<string>
     */
    public function files(string $directory = '', bool $recursive = false): array;

    /**
     * Get all directories in a directory.
     *
     * @return array<string>
     */
    public function directories(string $directory = '', bool $recursive = false): array;

    /**
     * Create a directory.
     */
    public function makeDirectory(string $path): bool;

    /**
     * Delete a directory.
     */
    public function deleteDirectory(string $directory): bool;

    /**
     * Get the URL for a file.
     */
    public function url(string $path): string;

    /**
     * Get a temporary URL for a file.
     */
    public function temporaryUrl(string $path, \DateTimeInterface $expiration): string;

    /**
     * Get the full path for a file.
     */
    public function path(string $path): string;
}
