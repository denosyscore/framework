<?php

declare(strict_types=1);

namespace Denosys\Filesystem\Exceptions;

use RuntimeException;

/**
 * Thrown when a file cannot be found.
 */
class FileNotFoundException extends RuntimeException
{
    public function __construct(string $path, ?\Throwable $previous = null)
    {
        parent::__construct("File not found: {$path}", 0, $previous);
    }
}
