# denosyscore/contracts

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/contracts`. Open issues and pull requests there.

Core framework contracts and interfaces

## Installation

```bash
composer require denosyscore/contracts
```

## Quick start

Interfaces shared by DenoSys packages. A package that only reads settings type-hints `ConfigReader`, and the host application supplies an implementation.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Contracts\Config\ConfigReader;

// Packages type-hint the read-only contract; the host decides where settings live.
final class ArrayConfiguration implements ConfigReader
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

function cacheDirectory(ConfigReader $config): string
{
    return $config->get('cache.path', '/tmp/cache');
}

echo cacheDirectory(new ArrayConfiguration(['cache' => ['path' => '/var/cache/app']])), "\n";
// /var/cache/app
```

## Contracts

| Interface | Purpose |
|---|---|
| `Denosys\Contracts\Config\ConfigReader` | Read a setting by dot path: `get(string $key, mixed $default = null): mixed`. Packages that only read configuration type-hint this; the host supplies the implementation. |
| `Denosys\Contracts\View\TemplateRenderer` | Render a named template to a string. An HTTP response factory or a mailer type-hints this instead of a particular template engine. |

This package has no dependencies. Service provider interfaces live in
`denosyscore/framework` (`Denosys\Framework\Provider`), because providers are
how the framework wires packages together.

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
