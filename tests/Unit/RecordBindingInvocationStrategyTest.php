<?php

declare(strict_types=1);

namespace Tests\Unit;

use Denosys\Container\Container;
use Denosys\Framework\Routing\Strategy\RecordBindingInvocationStrategy;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;

final class StaticStringHandler
{
    public static function show(string $id): HtmlResponse
    {
        return new HtmlResponse("item {$id}");
    }
}

final class RecordBindingInvocationStrategyTest extends TestCase
{
    public function testAStaticMethodStringHandlerIsInvoked(): void
    {
        $response = (new RecordBindingInvocationStrategy(new Container()))
            ->invoke(StaticStringHandler::class . '::show', new ServerRequest(), ['id' => '7']);

        self::assertSame('item 7', (string) $response->getBody());
    }
}
