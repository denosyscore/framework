<?php

declare(strict_types = 1);

namespace Denosys\Environment;

use Dotenv\Dotenv;
use PhpOption\Option;
use InvalidArgumentException;
use Dotenv\Repository\RepositoryInterface;
use Dotenv\Repository\RepositoryBuilder;
use Dotenv\Repository\Adapter\PutenvAdapter;

class EnvironmentManager
{
    /**
     * @param array<string, mixed> $cache
     */
    private RepositoryInterface $repository;
    /** @var array<string, mixed> */

    private array $cache = [];

    public function __construct(?RepositoryInterface $repository = null)
    {
        $this->repository = $repository ?? $this->createDefaultRepository();
    }

    /**
     * @param array<string, mixed> $files
     */
    public function load(string $dir, array $files = ['.env']): void
    {
        if (!is_dir($dir)) {
            throw new InvalidArgumentException("Env directory not found: {$dir}");
        }

        Dotenv::create($this->repository, $dir, $files)->safeLoad();
        $this->cache = [];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (!isset($this->cache[$key])) {
            $value = Option::fromValue($this->repository->get($key))
                ->map(
                    fn ($value) => $this->cast($value)
                )->getOrElse($default);

            $this->cache[$key] = $value;
        }

        return $this->cache[$key];
    }

    private function cast(string $value): mixed
    {
        return match (strtolower($value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $this->castNumericIfExplicit($value),
        };
    }

    /**
     * Casts a given string to a numeric type if explicitly marked.
     * E.g., "(int)123" or "(float)12.5"
     *
     */
    private function castNumericIfExplicit(string $value): string|int|float
    {
        if (preg_match('/^\(int\)(.+)$/', $value, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^\(float\)(.+)$/', $value, $matches)) {
            return (float) $matches[1];
        }

        return $value;
    }

    private function createDefaultRepository(): RepositoryInterface
    {
        return RepositoryBuilder::createWithDefaultAdapters()
            ->addAdapter(PutenvAdapter::class)
            ->immutable()
            ->make();
    }
}
