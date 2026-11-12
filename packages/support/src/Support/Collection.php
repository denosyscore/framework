<?php

declare(strict_types=1);

namespace Denosys\Support;

use Illuminate\Support\Collection as IlluminateCollection;

/**
 * A stable collection type for package APIs that return sequences of values.
 *
 * @template TValue
 * @extends IlluminateCollection<array-key, TValue>
 */
class Collection extends IlluminateCollection
{
}
