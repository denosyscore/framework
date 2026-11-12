<?php

declare(strict_types=1);

use Denosys\Config\Repository;
use Denosys\Contracts\Config\ConfigReader;
use Denosys\Container\Container;
use Denosys\Routing\Router;
use Denosys\Framework\Providers\RouterServiceProvider;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;

function frameworkRouterForHttpStatusTest(): Router
{
    $container = new Container();
    $config = new Repository();
    $container->instance(Repository::class, $config);
    $container->instance(ConfigReader::class, $config);
    (new RouterServiceProvider())->register($container);

    return $container->get(Router::class);
}

it('returns a 404 response for an unknown route through the default router', function (): void {
    $response = frameworkRouterForHttpStatusTest()
        ->dispatch(new ServerRequest([], [], 'https://example.test/missing', 'GET'));

    expect($response->getStatusCode())->toBe(404);
    expect($response->getHeaderLine('Access-Control-Allow-Origin'))->toBe('*');
});

it('returns a 405 response for an unsupported method through the default router', function (): void {
    $router = frameworkRouterForHttpStatusTest();
    $router->get('/known', static fn (): HtmlResponse => new HtmlResponse('known'));

    $response = $router->dispatch(new ServerRequest([], [], 'https://example.test/known', 'POST'));

    expect($response->getStatusCode())->toBe(405);
});

it('still propagates non-HTTP exceptions from route handlers', function (): void {
    $router = frameworkRouterForHttpStatusTest();
    $router->get('/broken', static function (): never {
        throw new RuntimeException('unexpected failure');
    });

    expect(fn () => $router->dispatch(new ServerRequest([], [], 'https://example.test/broken', 'GET')))
        ->toThrow(RuntimeException::class, 'unexpected failure');
});
