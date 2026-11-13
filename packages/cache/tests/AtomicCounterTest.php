<?php

declare(strict_types=1);

use Denosys\Cache\AtomicCounter;
use Denosys\Cache\FileCache;
use PHPUnit\Framework\TestCase;

final class AtomicCounterTest extends TestCase
{
    private string $directory;
    private FileCache $cache;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/denosys-counter-test-' . bin2hex(random_bytes(4));
        $this->cache = new FileCache($this->directory);
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

    public function testFileCacheIsAnAtomicCounterAndAPsr16Cache(): void
    {
        self::assertInstanceOf(AtomicCounter::class, $this->cache);
        self::assertInstanceOf(Psr\SimpleCache\CacheInterface::class, $this->cache);
    }

    public function testIncrementsCountFromOneAndReportTheCurrentValue(): void
    {
        self::assertSame(0, $this->cache->current('hits'));
        self::assertSame(1, $this->cache->increment('hits'));
        self::assertSame(4, $this->cache->increment('hits', 3, 60));
        self::assertSame(4, $this->cache->current('hits'));
    }

    public function testTheTtlIsSetWhenTheCounterStartsAndNotExtended(): void
    {
        $this->cache->increment('hits', 1, 1);
        sleep(1);
        $this->cache->increment('hits', 1, 60);
        sleep(1);

        self::assertSame(0, $this->cache->current('hits'), 'later increments do not extend the first window');
        self::assertSame(1, $this->cache->increment('hits', 1, 60), 'an expired counter starts again');
    }

    public function testResetForgetsTheCounter(): void
    {
        $this->cache->increment('hits', 5);

        $this->cache->reset('hits');

        self::assertSame(0, $this->cache->current('hits'));
    }

    public function testATtlBelowOneIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->cache->increment('hits', 1, 0);
    }
}
