<?php

declare(strict_types=1);

use Denosys\Environment\EnvironmentManager;
use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;

it('ignores a missing optional env file without a PHP warning', function (): void {
    $directory = sys_get_temp_dir() . '/denosys-env-' . bin2hex(random_bytes(8));
    expect(mkdir($directory, 0700))->toBeTrue();

    $warnings = [];
    set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
        $warnings[] = $message;

        return true;
    });

    try {
        (new EnvironmentManager())->load($directory);
    } finally {
        restore_error_handler();
        expect(rmdir($directory))->toBeTrue();
    }

    expect($warnings)->toBe([]);
});

it('loads a present env file after an optional missing file', function (): void {
    $directory = sys_get_temp_dir() . '/denosys-env-' . bin2hex(random_bytes(8));
    expect(mkdir($directory, 0700))->toBeTrue();
    $presentFile = $directory . '/.env.local';
    expect(file_put_contents($presentFile, "DENOSYS_ENV_TEST=loaded\n"))->not->toBeFalse();

    $repository = RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(ArrayAdapter::class)
        ->make();
    $environment = new EnvironmentManager($repository);
    $warnings = [];
    set_error_handler(static function (int $severity, string $message) use (&$warnings): bool {
        $warnings[] = $message;

        return true;
    });

    try {
        $environment->load($directory, ['.env', '.env.local']);
    } finally {
        restore_error_handler();
        expect(unlink($presentFile))->toBeTrue();
        expect(rmdir($directory))->toBeTrue();
    }

    expect($warnings)->toBe([]);
    expect($environment->get('DENOSYS_ENV_TEST'))->toBe('loaded');
});

it('rejects a nonexistent environment directory', function (): void {
    $directory = sys_get_temp_dir() . '/denosys-env-' . bin2hex(random_bytes(8));

    expect(fn () => (new EnvironmentManager())->load($directory))
        ->toThrow(InvalidArgumentException::class, 'Env directory not found');
});

it('resolves process environment values when PHP does not populate $_ENV', function (): void {
    $key = 'DENOSYS_PROCESS_ENV_TEST';
    $previous = getenv($key);
    $previousArrayValue = $_ENV[$key] ?? null;
    unset($_ENV[$key]);
    putenv($key . '=process-value');

    try {
        expect(env($key, 'fallback'))->toBe('process-value');
        putenv($key . '=false');
        expect(env($key, true))->toBeFalse();
    } finally {
        if ($previous === false) {
            putenv($key);
        } else {
            putenv($key . '=' . $previous);
        }
        if ($previousArrayValue === null) {
            unset($_ENV[$key]);
        } else {
            $_ENV[$key] = $previousArrayValue;
        }
    }
});

it('keeps an explicit $_ENV value ahead of the process fallback', function (): void {
    $key = 'DENOSYS_PROCESS_PRECEDENCE_TEST';
    $previous = getenv($key);
    $previousArrayValue = $_ENV[$key] ?? null;
    putenv($key . '=process-value');
    $_ENV[$key] = 'array-value';

    try {
        expect(env($key))->toBe('array-value');
    } finally {
        if ($previous === false) {
            putenv($key);
        } else {
            putenv($key . '=' . $previous);
        }
        if ($previousArrayValue === null) {
            unset($_ENV[$key]);
        } else {
            $_ENV[$key] = $previousArrayValue;
        }
    }
});
