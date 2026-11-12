<?php

declare(strict_types=1);

namespace Denosys\Config;

use Throwable;

/**
 * Loads configuration files and reads and writes the compiled cache.
 *
 * Each `<name>.php` file in the directory returns an array stored under
 * `<name>`. The cache is a PHP file written atomically, so a reader never
 * sees a partial write.
 */
final class ConfigLoader
{
    public function fromDirectory(string $directory): Repository
    {
        if (!is_dir($directory)) {
            throw ConfigException::invalidFile($directory, 'is not a directory');
        }

        $items = [];
        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);
        foreach ($files as $file) {
            $items[pathinfo($file, PATHINFO_FILENAME)] = $this->requireArray($file);
        }

        return new Repository($items);
    }

    public function fromCache(string $cacheFile): ?Repository
    {
        if (!is_file($cacheFile)) {
            return null;
        }

        $items = [];
        foreach ($this->requireArray($cacheFile) as $name => $values) {
            if (!is_string($name)) {
                throw ConfigException::invalidFile(basename($cacheFile), 'is not a configuration cache');
            }
            $items[$name] = $values;
        }

        return new Repository($items);
    }

    public function writeCache(Repository $config, string $cacheFile): void
    {
        $directory = dirname($cacheFile);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw ConfigException::invalidFile($cacheFile, 'cannot be written: its directory cannot be created');
        }

        $temporary = $cacheFile . '.' . bin2hex(random_bytes(6)) . '.tmp';
        $content = "<?php\n\nreturn " . var_export($config->all(), true) . ";\n";
        if (file_put_contents($temporary, $content, LOCK_EX) === false || !rename($temporary, $cacheFile)) {
            @unlink($temporary);
            throw ConfigException::invalidFile($cacheFile, 'cannot be written');
        }
    }

    public function clearCache(string $cacheFile): void
    {
        if (is_file($cacheFile)) {
            unlink($cacheFile);
        }
    }

    /**
     * @return array<mixed>
     */
    private function requireArray(string $file): array
    {
        try {
            $value = require $file;
        } catch (Throwable $exception) {
            throw ConfigException::invalidFile(basename($file), 'failed to load: ' . $exception->getMessage());
        }

        if (!is_array($value)) {
            throw ConfigException::invalidFile(basename($file), 'must return an array');
        }

        return $value;
    }
}
