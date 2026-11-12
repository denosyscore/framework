# denosyscore/hashing

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/hashing`. Open issues and pull requests there.

Password hashing services

## Installation

```bash
composer require denosyscore/hashing
```

## Quick start

Password hashing with bcrypt or Argon2id. It needs no container or configuration.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Hashing\HashManager;

$hash = new HashManager(['driver' => 'bcrypt']);  // or 'argon2id'

$stored = $hash->make('correct horse battery staple');

var_dump($hash->check('correct horse battery staple', $stored));  // bool(true)
var_dump($hash->check('wrong password', $stored));                // bool(false)

if ($hash->needsRehash($stored)) {
    $stored = $hash->make('correct horse battery staple');
}
```

## Included Modules

- src/Hashing/*

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
