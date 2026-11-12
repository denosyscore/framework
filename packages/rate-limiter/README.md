# denosyscore/rate-limiter

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/rate-limiter`. Open issues and pull requests there.

Rate limiting services and middleware

## Installation

```bash
composer require denosyscore/rate-limiter
```

## Quick start

Count attempts per key over a time window, stored in any DenoSys cache.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Cache\FileCache;
use Denosys\RateLimiter\RateLimiter;

$limiter = new RateLimiter(new FileCache(sys_get_temp_dir() . '/my-app-limits'));
$key = 'login:203.0.113.7';

for ($attempt = 1; $attempt <= 6; $attempt++) {
    if ($limiter->tooManyAttempts($key, maxAttempts: 5)) {
        echo "Locked out; try again in {$limiter->availableIn($key)}s\n";
        break;
    }
    $limiter->hit($key, decaySeconds: 60);
    echo "Attempt {$attempt}, {$limiter->remaining($key, 5)} left\n";
}

$limiter->clear($key);
```

## Included Modules

- src/RateLimiter/*

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
