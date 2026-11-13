<?php

declare(strict_types=1);

namespace Denosys\RateLimiter;

use Denosys\Cache\AtomicCounter;

/**
 * Counts attempts per key in a fixed window that starts at the first hit.
 * Hits are counted atomically, so concurrent requests are never lost.
 */
final class RateLimiter
{
    public function __construct(
        private readonly AtomicCounter $cache,
    ) {}

    /**
     * Record a hit (attempt) for the given key.
     *
     * @param string $key The throttle key (e.g., IP + action)
     * @param int $decaySeconds How long the window lasts from the first hit
     * @return int The total number of attempts after this hit
     */
    public function hit(string $key, int $decaySeconds = 60): int
    {
        return $this->cache->increment($this->attemptsKey($key), 1, $decaySeconds);
    }

    /**
     * Check if too many attempts have been made.
     *
     * @param string $key The throttle key
     * @param int $maxAttempts Maximum allowed attempts
     * @return bool True if limit exceeded
     */
    public function tooManyAttempts(string $key, int $maxAttempts): bool
    {
        return $this->attempts($key) >= $maxAttempts;
    }

    /**
     * Get the number of attempts for the given key.
     *
     * @param string $key The throttle key
     * @return int Current attempt count
     */
    public function attempts(string $key): int
    {
        return $this->cache->current($this->attemptsKey($key));
    }

    /**
     * Get remaining attempts allowed.
     *
     * @param string $key The throttle key
     * @param int $maxAttempts Maximum allowed attempts
     * @return int Remaining attempts (0 if exceeded)
     */
    public function remaining(string $key, int $maxAttempts): int
    {
        $remaining = $maxAttempts - $this->attempts($key);
        return max(0, $remaining);
    }

    /**
     * Reset the attempts for the given key.
     *
     * @param string $key The throttle key
     */
    public function clear(string $key): void
    {
        $this->cache->reset($this->attemptsKey($key));
    }

    /** Seconds until the window ends (0 when there is none). */
    public function availableIn(string $key): int
    {
        $expiresAt = $this->availableAt($key);

        return $expiresAt === null ? 0 : max(0, $expiresAt - time());
    }

    /** When the window ends (a Unix time), or null when there is none. */
    public function availableAt(string $key): ?int
    {
        return $this->cache->expiresAt($this->attemptsKey($key));
    }

    private function attemptsKey(string $key): string
    {
        return 'rate_limiter_' . $this->cacheSafe($key) . '_attempts';
    }

    /**
     * Hash the caller's key so any text (IPv6 addresses, emails, paths) maps
     * to a PSR-16-safe cache key without colliding with other keys.
     */
    private function cacheSafe(string $key): string
    {
        return hash('sha256', $key);
    }
}
