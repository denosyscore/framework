<?php

declare(strict_types=1);

namespace Denosys\Config\Tests;

use Denosys\Config\ConfigException;
use Denosys\Config\ConfigLoader;
use PHPUnit\Framework\TestCase;

final class ConfigLoaderTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/denosys-config-' . bin2hex(random_bytes(6));
        mkdir($this->directory . '/config', 0700, true);
        file_put_contents($this->directory . '/config/app.php', "<?php return ['name' => 'Shop', 'debug' => null];\n");
        file_put_contents($this->directory . '/config/cache.php', "<?php return ['path' => '/tmp/cache'];\n");
        file_put_contents($this->directory . '/config/notes.txt', 'ignored');
    }

    protected function tearDown(): void
    {
        foreach ([...glob($this->directory . '/config/*') ?: [], ...glob($this->directory . '/cache/*') ?: []] as $file) {
            unlink($file);
        }
        foreach (['/config', '/cache', ''] as $path) {
            if (is_dir($this->directory . $path)) {
                rmdir($this->directory . $path);
            }
        }
    }

    public function testLoadsEveryPhpFileUnderItsName(): void
    {
        $config = (new ConfigLoader())->fromDirectory($this->directory . '/config');

        self::assertSame('Shop', $config->get('app.name'));
        self::assertSame('/tmp/cache', $config->get('cache.path'));
        self::assertFalse($config->has('notes'));
    }

    public function testCacheRoundTripsIncludingNulls(): void
    {
        $loader = new ConfigLoader();
        $cache = $this->directory . '/cache/config.php';

        $loader->writeCache($loader->fromDirectory($this->directory . '/config'), $cache);
        $cached = $loader->fromCache($cache);

        self::assertNotNull($cached);
        self::assertSame('Shop', $cached->get('app.name'));
        self::assertTrue($cached->has('app.debug'));
    }

    public function testMissingCacheIsNull(): void
    {
        self::assertNull((new ConfigLoader())->fromCache($this->directory . '/cache/none.php'));
    }

    public function testAFileThatDoesNotReturnAnArrayIsAnError(): void
    {
        file_put_contents($this->directory . '/config/broken.php', "<?php return 'oops';\n");

        $this->expectException(ConfigException::class);
        $this->expectExceptionMessage('broken.php');
        (new ConfigLoader())->fromDirectory($this->directory . '/config');
    }
}
