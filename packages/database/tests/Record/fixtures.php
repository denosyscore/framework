<?php

declare(strict_types=1);

namespace RecordFixtures;

use DateTimeImmutable;
use Denosys\Database\Record\Attributes\CastWith;
use Denosys\Database\Record\Attributes\Column;
use Denosys\Database\Record\Attributes\Id;
use Denosys\Database\Record\Attributes\Table;
use Denosys\Database\Record\Cast;

final class BlogPost
{
    public ?int $id = null;
    public string $title;
    public string $firstName;
    #[Column('fname')]
    public string $nickName;
    public static int $count = 0;
    protected string $hiddenThing = '';
    private string $secret = '';
}

final class Category
{
    public ?int $id = null;
}

final class Box
{
    public ?int $id = null;
}

#[Table('people', connection: 'other')]
final class Person
{
    #[Id]
    public ?string $code = null;
    public string $name;
}

final class UnionTyped
{
    public ?int $id = null;
    public int|string $value;
}

final class Untyped
{
    public ?int $id = null;
    /** @phpstan-ignore missingType.property */
    public $value;
}

final class NoId
{
    public string $name;
}

enum Status: string
{
    case Active = 'active';
    case Closed = 'closed';
}

final class Money
{
    public function __construct(public readonly int $cents)
    {
    }
}

final class MoneyCast implements Cast
{
    public function fromDatabase(mixed $value): mixed
    {
        return new Money((int) round(((float) $value) * 100));
    }

    public function toDatabase(mixed $value): mixed
    {
        return $value instanceof Money ? number_format($value->cents / 100, 2, '.', '') : $value;
    }
}

final class Account
{
    public ?int $id = null;
    public int $count;
    public float $rate;
    public bool $active;
    public string $amount;
    public ?DateTimeImmutable $openedAt = null;
    public Status $status;
    #[CastWith(MoneyCast::class)]
    public Money $balance;
}
