<?php

declare(strict_types=1);

namespace Denosys\Filesystem\Exceptions;

use RuntimeException;
use Throwable;

/** The disk cannot do this, such as signing a temporary URL on a local disk. */
final class UnsupportedOperation extends RuntimeException implements FilesystemException
{
    public function __construct(string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
