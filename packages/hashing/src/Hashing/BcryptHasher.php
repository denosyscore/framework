<?php

declare(strict_types=1);

namespace Denosys\Hashing;

/**
 * Bcrypt hasher implementation.
 * 
 * Uses PHP's native password_hash with PASSWORD_BCRYPT algorithm.
 * Bcrypt is widely supported and considered secure for password hashing.
 */
class BcryptHasher implements HasherInterface
{
    /**
     * Default cost factor (4-31, higher = slower but more secure).
     */
    protected int $rounds = 12;

    /**
     * Create a new Bcrypt hasher instance.
     *
     * @param array<string, mixed> $options
     */
    public function __construct(array $options = [])
    {
        $this->rounds = $options['rounds'] ?? 12;
    }

    /**
     * Hash the given value using bcrypt.
      * @param array<string, mixed> $options
     */
    public function make(string $value, array $options = []): string
    {
        $cost = $options['rounds'] ?? $this->rounds;

        $hash = password_hash($value, PASSWORD_BCRYPT, [
            'cost' => $cost,
        ]);

        if ($hash === false) {
            throw new HashException('Bcrypt hashing not supported.');
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
        $cost = $options['rounds'] ?? $this->rounds;

        return password_needs_rehash($hashedValue, PASSWORD_BCRYPT, [
            'cost' => $cost,
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

    /**
     * Set the default cost factor.
     */
    public function setRounds(int $rounds): self
    {
        $this->rounds = $rounds;
        return $this;
    }
}
