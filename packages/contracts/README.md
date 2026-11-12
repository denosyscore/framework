# denosyscore/contracts

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/contracts`. Open issues and pull requests there.

Core framework contracts and interfaces

## Installation

```bash
composer require denosyscore/contracts
```

## Quick start

Interfaces shared by DenoSys packages. A package that only reads settings type-hints `ConfigurationReaderInterface`, and the host application supplies an implementation.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Contracts\Config\ConfigurationReaderInterface;

// Packages type-hint the read-only contract; the host decides where settings live.
final class ArrayConfiguration implements ConfigurationReaderInterface
{
    public function __construct(private array $settings) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->settings;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }
}

function cacheDirectory(ConfigurationReaderInterface $config): string
{
    return $config->get('cache.path', '/tmp/cache');
}

echo cacheDirectory(new ArrayConfiguration(['cache' => ['path' => '/var/cache/app']])), "\n";
// /var/cache/app
```

## Included Modules

- src/ServiceProviderInterface.php
- src/DeferrableProviderInterface.php
- src/Container/*Interface.php
- src/Config/ConfigurationReaderInterface.php
- src/Contracts/* (extracted contracts namespace)

`ConfigurationReaderInterface` exposes `get(string $key, mixed $default = null): mixed`
for packages that only read settings. A framework or standalone host supplies an
implementation through its container.

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
