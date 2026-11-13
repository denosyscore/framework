<?php

declare(strict_types=1);

namespace Tests\Unit;

use Denosys\Database\Connection\ConnectionManager;
use Denosys\Database\Record\Persists;
use Denosys\Database\Record\Record;
use Denosys\Database\Record\Records;
use Denosys\Database\Record\RecordStore;
use Denosys\Framework\Routing\ParameterResolvers\RecordBindingResolver;
use Denosys\Routing\Exceptions\NotFoundException;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use ReflectionFunction;
use ReflectionParameter;

final class BoundWidget implements Record
{
    use Persists;

    public ?int $id = null;
    public string $name;
}

final class RecordBindingResolverTest extends TestCase
{
    protected function setUp(): void
    {
        $manager = new ConnectionManager();
        $manager->addConnection('default', ['driver' => 'sqlite', 'database' => ':memory:']);
        $manager->setDefaultConnection('default');
        $manager->connection()->statement('CREATE TABLE bound_widgets (id INTEGER PRIMARY KEY, name TEXT NOT NULL)');
        $manager->connection()->table('bound_widgets')->insert(['name' => 'w']);
        Records::use(new RecordStore($manager));
    }

    protected function tearDown(): void
    {
        Records::reset();
    }

    private function parameter(): ReflectionParameter
    {
        return (new ReflectionFunction(static fn (BoundWidget $widget): BoundWidget => $widget))->getParameters()[0];
    }

    public function testARecordParameterIsLoadedByItsRouteArgument(): void
    {
        $resolver = new RecordBindingResolver();

        self::assertTrue($resolver->canResolve($this->parameter(), ['widget' => '1']));
        self::assertFalse($resolver->canResolve($this->parameter(), ['other' => '1']));
        $widget = $resolver->resolve($this->parameter(), new ServerRequest(), ['widget' => '1']);
        self::assertInstanceOf(BoundWidget::class, $widget);
        self::assertSame('w', $widget->name);
    }

    public function testAMissingRecordIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        (new RecordBindingResolver())->resolve($this->parameter(), new ServerRequest(), ['widget' => '9']);
    }

    public function testANonNumericArgumentForAnIntegerKeyIsNotFound(): void
    {
        $resolver = new RecordBindingResolver();

        foreach (['1abc', 'abc', '1.0', ''] as $argument) {
            try {
                $resolver->resolve($this->parameter(), new ServerRequest(), ['widget' => $argument]);
                self::fail("{$argument} resolved");
            } catch (NotFoundException) {
                self::addToAssertionCount(1);
            }
        }
    }

    public function testAnArgumentOutsideTheIntegerRangeIsNotFound(): void
    {
        Records::store()->connectionFor(BoundWidget::class)->table('bound_widgets')->insert(['id' => PHP_INT_MAX, 'name' => 'max']);

        $this->expectException(NotFoundException::class);

        (new RecordBindingResolver())->resolve($this->parameter(), new ServerRequest(), ['widget' => '9223372036854775808']);
    }
}
