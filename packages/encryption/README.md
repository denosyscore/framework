# denosyscore/encryption

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/encryption`. Open issues and pull requests there.

Encryption services

## Installation

```bash
composer require denosyscore/encryption
```

## Quick start

Authenticated encryption with a 32-byte key. It needs no container or configuration.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Encryption\Encrypter;

// Store the key outside your code, e.g. in APP_KEY.
$key = Encrypter::generateKey();
$encrypter = new Encrypter($key);

$payload = $encrypter->encryptString('card ending 4242');
echo $encrypter->decryptString($payload), "\n";  // card ending 4242

$token = $encrypter->encrypt(['user' => 42]);    // serialises arrays and objects
echo $encrypter->decrypt($token)['user'], "\n";  // 42
```

## Included Modules

- src/Encryption/*

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
