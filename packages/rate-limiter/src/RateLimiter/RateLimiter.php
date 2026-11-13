<?php

declare(strict_types=1);

namespace Denosys\RateLimiter;

use Denosys\Cache\AtomicCounter;
use Psr\SimpleCache\CacheInterface;

/**
 * Counts attempts per key in a fixed window that starts at the first hit.
 * Hits are counted atomically, so concurrent requests are never lost.
 */
final class RateLimiter
{
    public function __construct(
        private readonly AtomicCounter&CacheInterface $cache,
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
        $attempts = $this->cache->increment($this->attemptsKey($key), 1, $decaySeconds);
        if ($attempts === 1) {
            $this->cache->set($this->timerKey($key), time() + $decaySeconds, $decaySeconds);
        }

        return $attempts;
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
        $this->cache->delete($this->timerKey($key));
    }

    /**
     * Get the number of seconds until attempts reset.
     *
     * @param string $key The throttle key
     * @return int Seconds until reset (0 if not set)
     */
    public function availableIn(string $key): int
    {
        $expiresAt = $this->cache->get($this->timerKey($key));
        
        if ($expiresAt === null) {
            return 0;
        }

        return max(0, (int) $expiresAt - time());
    }

    /**
     * Get the UNIX timestamp when attempts will reset.
     *
     * @param string $key The throttle key
     * @return int|null Timestamp or null if not set
     */
    public function availableAt(string $key): ?int
    {
        $expiresAt = $this->cache->get($this->timerKey($key));
        
        if ($expiresAt === null) {
            return null;
        }

        return (int) $expiresAt;
    }

    /**
     * Get the cache key for attempts count.
     */
    private function attemptsKey(string $key): string
    {
        return 'rate_limiter_' . $this->cacheSafe($key) . '_attempts';
    }

    /**
     * Get the cache key for the decay timer.
     */
    private function timerKey(string $key): string
    {
        return 'rate_limiter_' . $this->cacheSafe($key) . '_timer';
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
