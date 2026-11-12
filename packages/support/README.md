# DenoSys Support

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/support`. Open issues and pull requests there.

Reusable support types for DenoSysCore packages.

## Collection

`Denosys\Support\Collection` is the collection type exposed by package APIs such
as hydrated database queries and eager-loaded relations. It extends the
maintained `Illuminate\Support\Collection`; the underlying collection
operations are not reimplemented here. The DenoSys type keeps package method
signatures stable while allowing a future implementation change if needed.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Support\Collection;

$orders = new Collection([
    ['id' => 1, 'total' => 120],
    ['id' => 2, 'total' => 80],
    ['id' => 3, 'total' => 200],
]);

print_r($orders->pluck('id')->all());                        // [1, 2, 3]
echo $orders->where('total', '>', 100)->sum('total'), "\n";  // 320
```

Requires PHP 8.2 or later. Install with `composer require denosyscore/support`.

## Quality checks

Run `composer test` and `composer analyse` after `composer install`.

## Releases

The initial collection contract is proposed for `v0.1.0`. A maintainer should
verify CI on `main`, create the annotated semantic version tag from that exact
commit, and push the tag. The Release workflow publishes GitHub release notes
for tags matching `v*.*.*`. Confirm the package index exposes the new version
before updating consumers from development branches to `^0.1`.
