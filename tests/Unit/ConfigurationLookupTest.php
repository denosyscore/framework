<?php

declare(strict_types=1);

use Denosys\Config\Configuration;

function configurationFrom(array $files): Configuration
{
    $directory = sys_get_temp_dir() . '/denosys-config-' . bin2hex(random_bytes(4));
    mkdir($directory);
    foreach ($files as $name => $values) {
        file_put_contents("{$directory}/{$name}.php", '<?php return ' . var_export($values, true) . ';');
    }

    $configuration = new Configuration($directory, $directory . '/cache/config.php');
    $configuration->loadFiles();

    return $configuration;
}

it('reports nested keys that exist', function () {
    $config = configurationFrom(['app' => ['name' => 'Demo', 'debug' => false, 'mail' => ['from' => null]]]);

    expect($config->has('app'))->toBeTrue()
        ->and($config->has('app.name'))->toBeTrue()
        ->and($config->has('app.debug'))->toBeTrue()
        ->and($config->has('app.mail.from'))->toBeTrue()
        ->and($config->has('app.missing'))->toBeFalse()
        ->and($config->has('app.name.length'))->toBeFalse()
        ->and($config->has('missing.key'))->toBeFalse();
});

it('returns stored null and false values instead of the default', function () {
    $config = configurationFrom(['app' => ['debug' => false, 'mail' => ['from' => null]]]);

    expect($config->get('app.debug', true))->toBeFalse()
        ->and($config->get('app.mail.from', 'fallback@example.com'))->toBeNull();
});

it('returns the default for missing keys without indexing into it', function () {
    $config = configurationFrom(['app' => ['name' => 'Demo']]);

    expect($config->get('app.missing.deeper', ['a' => 1]))->toBe(['a' => 1])
        ->and($config->get('missing', 'x'))->toBe('x')
        ->and($config->get('app.name.length', 'x'))->toBe('x');
});

it('returns whole sections and leaf values', function () {
    $config = configurationFrom(['app' => ['name' => 'Demo']]);

    expect($config->get('app'))->toBe(['name' => 'Demo'])
        ->and($config->get('app.name'))->toBe('Demo');
});
