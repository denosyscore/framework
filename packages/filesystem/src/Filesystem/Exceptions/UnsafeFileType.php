<?php

declare(strict_types=1);

namespace Denosys\Filesystem\Exceptions;

use RuntimeException;

/** An upload whose content or name is a type a web server could run or render. */
final class UnsafeFileType extends RuntimeException implements FilesystemException
{
    public function __construct(string $type)
    {
        parent::__construct("Files of type {$type} cannot be stored.");
    }
}
