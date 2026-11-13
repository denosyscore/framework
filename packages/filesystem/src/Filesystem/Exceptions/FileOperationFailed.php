<?php

declare(strict_types=1);

namespace Denosys\Filesystem\Exceptions;

use RuntimeException;
use Throwable;

/** A delete, copy, move, directory or metadata operation failed. */
final class FileOperationFailed extends RuntimeException implements FilesystemException
{
    public function __construct(string $operation, string $path, ?Throwable $previous = null)
    {
        parent::__construct("Unable to {$operation} {$path}.", 0, $previous);
    }
}
