# denosyscore/encryption

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/encryption`. Open issues and pull requests there.

Encryption services

## Installation

```bash
composer require denosyscore/encryption
```

## Quick start

Authenticated encryption of strings with a key ring. It needs no container or configuration.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Encryption\Encrypter;
use Denosys\Encryption\Key;
use Denosys\Encryption\KeyRing;

// Store keys outside your code, e.g. in APP_KEY.
$key = Key::fromBase64(Encrypter::generateKey());
$encrypter = new Encrypter(new KeyRing($key));

$payload = $encrypter->encrypt('card ending 4242');
echo $encrypter->decrypt($payload), "\n";              // card ending 4242

$token = $encrypter->encrypt(json_encode(['user' => 42], JSON_THROW_ON_ERROR));
echo json_decode($encrypter->decrypt($token), true)['user'], "\n";  // 42
```

## Strings only

`encrypt()` and `decrypt()` take and return strings. Nothing is serialized
or unserialized, so a decrypted payload can never create objects; encode
structured data as JSON yourself.

## Rotating keys

A payload names the key that made it: `v1.<key id>.<data>`, where the id is
the first eight hex characters of the key's SHA-256. To rotate, make the new
key current and keep the old one in the ring:

```php
$encrypter = new Encrypter(new KeyRing(Key::fromBase64($newKey), Key::fromBase64($oldKey)));

if ($encrypter->needsReEncryption($stored)) {
    $stored = $encrypter->reEncrypt($stored);
}
```

Remove the old key once nothing encrypted with it remains. Payloads written
before key ids (a bare base64 string) are tried with every key in the ring.
Values 0.21 encrypted with its default serialization decrypt to the
serialized text; re-encrypt them once as JSON when upgrading.
A malformed or tampered payload, or one from a key not in the ring, throws
`DecryptException`.

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
