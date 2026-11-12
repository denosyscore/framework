<?php

declare(strict_types=1);

namespace Denosys\Config;

use Dotenv\Dotenv;
use Dotenv\Repository\Adapter\PutenvAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\RepositoryInterface;

/**
 * The one way to read environment variables.
 *
 * Values are cast the same way everywhere: `true`, `false`, `null` and
 * `empty` (optionally in parentheses), `(int)42` and `(float)1.5`. Anything
 * else stays a string. Each read uses its own default.
 */
final class Environment
{
    private readonly RepositoryInterface $repository;

    public function __construct(?RepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();
    }

    /**
     * Load the dotenv files that exist in a directory, in order. Variables
     * already set in the process environment are not overwritten.
     *
     * @param list<string> $files
     */
    public function load(string $directory, array $files = ['.env']): void
    {
        if (!is_dir($directory)) {
            throw new \InvalidArgumentException("Env directory not found: {$directory}");
        }

        $present = array_values(array_filter(
            $files,
            static fn (string $file): bool => is_file($directory . DIRECTORY_SEPARATOR . $file),
        ));

        if ($present !== []) {
            Dotenv::create($this->repository, $directory, $present)->safeLoad();
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->repository->get($key);

        return $value === null ? $default : self::cast($value);
    }

    public function has(string $key): bool
    {
        return $this->repository->has($key);
    }

    public static function cast(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => match (true) {
                (bool) preg_match('/^\(int\)(-?\d+)$/', $value, $match) => (int) $match[1],
                (bool) preg_match('/^\(float\)(-?\d+(?:\.\d+)?)$/', $value, $match) => (float) $match[1],
                default => $value,
            },
        };
    }
}
