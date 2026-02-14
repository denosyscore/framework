<?php

declare(strict_types=1);

namespace App\Models;

use CFXP\Core\Auth\Identity\Authenticatable;
use CFXP\Core\Auth\Identity\AuthenticatableInterface;
use CFXP\Core\Database\Model;

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
