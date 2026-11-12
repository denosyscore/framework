<?php

declare(strict_types=1);

namespace Denosys\Filesystem\Exceptions;

use RuntimeException;

/**
 * Thrown when a file cannot be written.
 */
class FileWriteException extends RuntimeException
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct("Failed to write file: {$path}", 0, $previous);
    }
}
