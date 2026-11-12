<?php

declare(strict_types=1);

namespace Denosys\RateLimiter\Tests;

use Denosys\Cache\FileCache;
use Denosys\RateLimiter\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RateLimiterTest extends TestCase
{
    private string $directory;
    private RateLimiter $limiter;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/denosys-rate-limiter-' . bin2hex(random_bytes(6));
        $this->limiter = new RateLimiter(new FileCache($this->directory));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    /** @return iterable<string, array{string}> */
    public static function keys(): iterable
    {
        yield 'action and IPv4' => ['login:203.0.113.7'];
        yield 'IPv6' => ['2001:db8::1'];
        yield 'email' => ['reset:someone@example.com'];
        yield 'path' => ['GET /orders/{id}'];
    }

    /** Callers pass natural keys; the limiter must not leak cache key rules to them. */
    #[DataProvider('keys')]
    public function testAcceptsKeysWithCacheReservedCharacters(string $key): void
    {
        self::assertSame(1, $this->limiter->hit($key, 60));
        self::assertSame(2, $this->limiter->hit($key, 60));
        self::assertTrue($this->limiter->tooManyAttempts($key, 2));
        self::assertSame(0, $this->limiter->remaining($key, 2));

        $this->limiter->clear($key);
        self::assertSame(0, $this->limiter->attempts($key));
    }

    public function testKeysAreCountedSeparately(): void
    {
        $this->limiter->hit('login:203.0.113.7', 60);
        $this->limiter->hit('login:203.0.113.7', 60);
        $this->limiter->hit('login:203.0.113.8', 60);

        self::assertSame(2, $this->limiter->attempts('login:203.0.113.7'));
        self::assertSame(1, $this->limiter->attempts('login:203.0.113.8'));
    }

    public function testReportsWhenTheWindowEnds(): void
    {
        $this->limiter->hit('api', 60);

        self::assertGreaterThan(0, $this->limiter->availableIn('api'));
        self::assertLessThanOrEqual(60, $this->limiter->availableIn('api'));
        self::assertGreaterThan(time(), $this->limiter->availableAt('api'));
    }
}
