# denosyscore/filesystem

> **Read-only split.** This package is developed in [denosyscore/framework](https://github.com/denosyscore/framework) under `packages/filesystem`. Open issues and pull requests there.

Filesystem management and disk abstractions

## Installation

```bash
composer require denosyscore/filesystem
```

Version 0.3.1 requires `denosyscore/contracts` 0.2.4 or newer and
`denosyscore/container` and `denosyscore/events` 0.2.2 or newer within their
0.2 series. The explicit minimums exclude earlier releases with unusable
autoload mappings, including when Composer selects the lowest allowed versions.
Version 0.3.2 also excludes third-party releases with published advisories
from the supported dependency range. CI audits current and lowest Composer
resolutions so a supported installation can select a clean set.
Version 0.3.3 requires Symfony Filesystem 7.4.23 or newer in the 7.x line
because earlier releases are affected by GHSA-6prc-h956-hxjq.

## Quick start

Read and write files on configured disks. The manager needs only a configuration reader.

<!-- quickstart -->
```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Denosys\Contracts\Config\ConfigReader;
use Denosys\Filesystem\FilesystemManager;

// Any object that reads settings by dot path works as configuration.
$config = new class implements ConfigReader {
    private array $settings = [
        'filesystems.default' => 'local',
        'filesystems.disks.local' => ['driver' => 'local'],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }
};

$storage = new FilesystemManager($config, sys_get_temp_dir() . '/my-app-storage');
$disk = $storage->disk();

$disk->put('reports/today.txt', 'All systems normal');
echo $disk->get('reports/today.txt'), "\n";  // All systems normal
$disk->delete('reports/today.txt');
```

## Configuration

`FilesystemManager` takes any `Denosys\Contracts\Config\ConfigReader` and reads
`filesystems.default` and `filesystems.disks.<name>` from it. Its second
argument is the root for local disks without an explicit `root`; without it the
manager uses `getcwd() . '/storage/app'`.

In a DenoSys application the framework's `FilesystemServiceProvider` builds the
manager from the application configuration, using the `app` directory under
`path.storage` as the default local root.

## Included Modules

- src/Filesystem/*

## Development

composer validate --strict
find src -type f -name '*.php' -print0 | xargs -0 -n1 php -l
composer test
