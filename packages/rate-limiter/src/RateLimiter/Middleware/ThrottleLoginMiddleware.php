<?php

declare(strict_types=1);

namespace Denosys\RateLimiter\Middleware;

use Denosys\Http\Middleware\ThrottleRequestsMiddleware;
use Denosys\RateLimiter\RateLimiter;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ThrottleLoginMiddleware implements MiddlewareInterface
{
    private ThrottleRequestsMiddleware $throttle;

    public function __construct(
        RateLimiter $rateLimiter,
        int $maxAttempts = 5,
        int $decayMinutes = 1,
    ) {
        $this->throttle = new ThrottleRequestsMiddleware(
            $rateLimiter,
            $maxAttempts,
            $decayMinutes,
            'login'
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->throttle->process($request, $handler);
    }
}
