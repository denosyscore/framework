<?php

declare(strict_types=1);

namespace Denosys\Hashing;

/**
 * Argon2id hasher implementation.
 * 
 * Uses PHP's native password_hash with PASSWORD_ARGON2ID algorithm.
 * Argon2id is the recommended algorithm for new applications (PHP 7.3+).
 */
class Argon2IdHasher implements HasherInterface
{
    /**
     * Default memory cost (in kibibytes).
     */
    protected int $memory = 65536; // 64 MB

    /**
     * Default time cost (iterations).
     */
    protected int $time = 4;

    /**
     * Default number of threads.
     */
    protected int $threads = 1;

    /**
     * Create a new Argon2id hasher instance.
     *
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->memory = $options['memory'] ?? 65536;
        $this->time = $options['time'] ?? 4;
        $this->threads = $options['threads'] ?? 1;
    }

    /**
     * Hash the given value using Argon2id.
      * @param array<string, mixed> $options
     */
    public function make(string $value, array $options = []): string
    {
        $hash = password_hash($value, PASSWORD_ARGON2ID, [
            'memory_cost' => $options['memory'] ?? $this->memory,
            'time_cost' => $options['time'] ?? $this->time,
            'threads' => $options['threads'] ?? $this->threads,
        ]);

        if ($hash === false) {
            throw new HashException('Argon2id hashing not supported.');
        }

        return $hash;
    }

    /**
     * Check the given plain value against a hash.
     */
    public function check(string $value, string $hashedValue): bool
    {
        if ($hashedValue === '') {
            return false;
        }

        return password_verify($value, $hashedValue);
    }

    /**
     * Check if the given hash needs to be rehashed.
      * @param array<string, mixed> $options
     */
    public function needsRehash(string $hashedValue, array $options = []): bool
    {
        return password_needs_rehash($hashedValue, PASSWORD_ARGON2ID, [
            'memory_cost' => $options['memory'] ?? $this->memory,
            'time_cost' => $options['time'] ?? $this->time,
            'threads' => $options['threads'] ?? $this->threads,
        ]);
    }

    /**
     * Get information about the given hashed value.
     */
    /**
     * @return array<string, mixed>
     */
public function info(string $hashedValue): array
    {
        return password_get_info($hashedValue);
    }
}
