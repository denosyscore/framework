<?php

declare(strict_types=1);

namespace Denosys\Events\Tests;

use Denosys\Events\Dispatcher;
use Denosys\Events\ListenerProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

interface Shipment {}

final class OrderShipped implements Shipment
{
    /** @var list<string> */
    public array $handledBy = [];
}

final class SendShippingEmail
{
    public static int $built = 0;

    public function __construct()
    {
        self::$built++;
    }

    public function __invoke(OrderShipped $event): void
    {
        $event->handledBy[] = 'email';
    }
}

final class NotInvokable {}

final class ClassContainer implements ContainerInterface
{
    public function get(string $id): mixed
    {
        return new $id();
    }

    public function has(string $id): bool
    {
        return class_exists($id);
    }
}

final class ListenerProviderTest extends TestCase
{
    protected function setUp(): void
    {
        SendShippingEmail::$built = 0;
    }

    public function testClassListenersAreBuiltOnlyWhenTheirEventFires(): void
    {
        $listeners = new ListenerProvider(new ClassContainer());
        $listeners->on(OrderShipped::class, SendShippingEmail::class);
        self::assertSame(0, SendShippingEmail::$built, 'Registering must not build the listener.');

        $event = (new Dispatcher($listeners))->dispatch(new OrderShipped());

        self::assertSame(1, SendShippingEmail::$built);
        self::assertSame(['email'], $event->handledBy);
    }

    public function testCallableAndClassListenersRunInRegistrationOrder(): void
    {
        $listeners = new ListenerProvider(new ClassContainer());
        $listeners->on(OrderShipped::class, static function (OrderShipped $event): void {
            $event->handledBy[] = 'first';
        });
        $listeners->on(OrderShipped::class, SendShippingEmail::class);
        $listeners->addListener(OrderShipped::class, static function (OrderShipped $event): void {
            $event->handledBy[] = 'last';
        });

        self::assertSame(['first', 'email', 'last'], (new Dispatcher($listeners))->dispatch(new OrderShipped())->handledBy);
    }

    public function testListenersForInterfacesAndParentsStillMatch(): void
    {
        $listeners = new ListenerProvider();
        $listeners->on(Shipment::class, static function (OrderShipped $event): void {
            $event->handledBy[] = 'interface';
        });

        self::assertSame(['interface'], (new Dispatcher($listeners))->dispatch(new OrderShipped())->handledBy);
    }

    public function testClassListenersNeedAContainer(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('container');
        (new ListenerProvider())->on(OrderShipped::class, SendShippingEmail::class);
    }

    public function testClassListenersMustBeInvokable(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('NotInvokable');
        (new ListenerProvider(new ClassContainer()))->on(OrderShipped::class, NotInvokable::class);
    }
}
