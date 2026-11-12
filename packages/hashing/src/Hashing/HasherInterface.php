<?php

declare(strict_types=1);

namespace Denosys\Hashing;

/**
 * Contract for hashing implementations.
 */
interface HasherInterface
{
    /**
     * Hash the given value.
     *
     * @param string $value
     * @param array<string, mixed> $options
     * @return string
     * @throws HashException
     */
    public function make(string $value, array $options = []): string;

    /**
     * Check the given plain value against a hash.
     *
     * @param string $value
     * @param string $hashedValue
     * @return bool
     */
    public function check(string $value, string $hashedValue): bool;

    /**
     * Check if the given hash has been hashed using the given options.
     *
     * @param string $hashedValue
     * @param array<string, mixed> $options
     * @return bool
     */
    public function needsRehash(string $hashedValue, array $options = []): bool;

    /**
     * Get information about the given hashed value.
     *
     * @param string $hashedValue
     * @return array<string, mixed>
     */
    public function info(string $hashedValue): array;
}
