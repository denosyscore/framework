# denosyscore/config

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/config`. Open issues and pull requests there.

Configuration read by dot path, with typed reads, typed sections and one
environment reader. It has no framework dependency.

## Installation

```bash
composer require denosyscore/config
```

## Quick start

A stored `null` is a value: `get()` returns it rather than the default, and
`has()` reports it. Typed readers throw a `ConfigException` that names the
path when a value is missing or has the wrong type.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Config\ConfigSection;
use Denosys\Config\Repository;

$config = new Repository([
    'app' => ['name' => 'Shop', 'debug' => null, 'port' => 8080],
    'mail' => ['from' => ['address' => 'shop@example.test', 'name' => 'Shop']],
]);

var_dump($config->get('app.debug', true));  // NULL: the stored value, not the default
var_dump($config->has('app.debug'));        // bool(true)
echo $config->int('app.port'), "\n";        // 8080

final readonly class MailFrom implements ConfigSection
{
    public function __construct(public string $address, public string $name) {}

    public static function from(Repository $config): static
    {
        return new static($config->string('mail.from.address'), $config->string('mail.from.name'));
    }
}

echo $config->section(MailFrom::class)->address, "\n";  // shop@example.test
```

## Loading files

`ConfigLoader::fromDirectory()` loads every `<name>.php` file that returns an
array, under `<name>`. `writeCache()` compiles the result to one PHP file,
written atomically, and `fromCache()` reads it back.

## Environment

`Environment` is the one way to read environment variables. It loads dotenv
files without overwriting variables already set, and casts values one way
everywhere: `true`, `false`, `null` and `empty` (optionally in parentheses),
`(int)42`, `(float)1.5`; anything else stays a string.
