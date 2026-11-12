<?php

declare(strict_types=1);

namespace Denosys\Events;

use Psr\Http\Message\ServerRequestInterface;

class RequestHandling
{
    public function __construct(
        public readonly ServerRequestInterface $request
    ) {}
}
