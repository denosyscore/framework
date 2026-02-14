<?php

declare(strict_types=1);

namespace App\Models;

use Denosys\Auth\Identity\Authenticatable;
use Denosys\Auth\Identity\AuthenticatableInterface;
use Denosys\Database\Model;

class User extends Model implements AuthenticatableInterface
{
    use Authenticatable;

    protected string $table = 'users';

    /** @var array<int, string> */
    protected array $fillable = [
        'name',
        'email',
        'password',
    ];

    /** @var array<int, string> */
    protected array $hidden = [
        'password',
        'remember_token',
    ];
}
