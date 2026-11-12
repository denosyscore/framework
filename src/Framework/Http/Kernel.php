<?php

declare(strict_types=1);

namespace Denosys\Framework\Http;

use Denosys\Http\Request;
use Throwable;
use Denosys\Routing\Router;
use Denosys\Container\ContainerInterface;
use Denosys\Events\ResponseReady;
use Denosys\Events\RequestHandling;
use Denosys\Http\Internal\SecurityHeaders;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\ServerRequestFactory;
use Denosys\Container\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Denosys\Container\Exceptions\ContainerException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Denosys\Container\Exceptions\ContainerResolutionException;
use Denosys\Http\Exceptions\ExceptionHandler;

class Kernel
{
    public function __construct(
        protected readonly ContainerInterface $container,
    ) {
    }

    /**
     * Handle a request with the application the caller has already booted.
     */
    public function handle(?ServerRequestInterface $request = null): ResponseInterface
    {
        // Create request if not provided
        if ($request === null) {
            $request = ServerRequestFactory::fromGlobals();
        }

        // Wrap request with enhanced Request if not already wrapped
        if (!$request instanceof Request) {
            $request = Request::createFromPsr7($request);
        }

        // Bind request in container
        $this->container->instance(ServerRequestInterface::class, $request);
        $this->container->alias('request', ServerRequestInterface::class);

        // Dispatch request handling event
        $this->dispatcher()->dispatch(new RequestHandling($request));

        // Get router and dispatch request
        /** @var Router $router */
        $router = $this->container->get(Router::class);
        $response = $router->dispatch($request);
        $response = (new SecurityHeaders())->apply($request, $response);

        // Dispatch response ready event
        $this->dispatcher()->dispatch(new ResponseReady($response));

        return $response;
    }

    /**
     * Emit response to client.
     */
    public function emit(ResponseInterface $response): void
    {
        (new SapiEmitter())->emit($response);
    }

    /**
     * Complete request handling - handle and emit.
     *
     * @throws ContainerResolutionException
     */
    public function run(?ServerRequestInterface $request = null): void
    {
        $response = $this->handle($request);
        $this->emit($response);
    }

    private function dispatcher(): EventDispatcherInterface
    {
        $dispatcher = $this->container->get(EventDispatcherInterface::class);
        if (!$dispatcher instanceof EventDispatcherInterface) {
            throw new \LogicException('Boot the application before handling requests: no event dispatcher is bound.');
        }

        return $dispatcher;
    }
}
