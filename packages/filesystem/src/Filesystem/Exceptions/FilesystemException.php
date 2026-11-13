<?php

declare(strict_types=1);

namespace Denosys\Filesystem\Exceptions;

use Throwable;

/** Every filesystem failure implements this; the adapter's cause is getPrevious(). */
interface FilesystemException extends Throwable
{
}
