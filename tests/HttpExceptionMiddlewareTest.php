<?php

declare(strict_types=1);

namespace Denosys\Http\Tests;

use Denosys\Http\Middleware\HttpExceptionMiddleware;
use Denosys\Routing\Exceptions\MethodNotAllowedException;
use Denosys\Routing\Exceptions\NotFoundException;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class HttpExceptionMiddlewareTest extends TestCase
{
    public function testMethodNotAllowedResponseListsAllowedMethods(): void
    {
        $response = (new HttpExceptionMiddleware(new ResponseFactory()))->process(
            new ServerRequest([], [], 'https://example.test/resource', 'POST'),
            $this->throwingHandler(new MethodNotAllowedException(['GET', 'HEAD'])),
        );

        self::assertSame(405, $response->getStatusCode());
        self::assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
        self::assertSame('text/plain', $response->getHeaderLine('Content-Type'));
    }

    public function testNotFoundResponseDoesNotInventAllowedMethods(): void
    {
        $response = (new HttpExceptionMiddleware(new ResponseFactory()))->process(
            new ServerRequest([], [], 'https://example.test/missing', 'GET'),
            $this->throwingHandler(new NotFoundException()),
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertFalse($response->hasHeader('Allow'));
    }

    private function throwingHandler(Throwable $exception): RequestHandlerInterface
    {
        return new class($exception) implements RequestHandlerInterface {
            public function __construct(private readonly Throwable $exception)
            {
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                throw $this->exception;
            }
        };
    }
}
