<?php

declare(strict_types=1);

namespace Denosys\Config;

use Throwable;
use RuntimeException;

class Configuration implements ConfigurationInterface
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function __construct(
        private readonly string $configDirectory = '',
        private readonly string $cacheFile = ''
    ) {
    }

    /**
     * Retrieves the value associated with the given key
     * or returns the default value if it is set.
     *
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments  = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            $value = $value[$segment] ?? $default;
        }

        return $value;
    }

    /**
     * Check if a configuration value exists
     *
     * @param string $key
     *
     * @return bool
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * Retrieves all configuration value from the configuration file.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }

    public function loadFiles(): void
    {
        if (!empty($this->config)) {
            return;
        }

        $cachedConfig = $this->loadFromCache();

        if (null !== $cachedConfig && !empty($cachedConfig)) {
            $this->config = $cachedConfig;
        } else {
            $config = $this->loadFromDirectory();
            $this->config = $config;
        }
    }

    public function saveToCache(): void
    {
        $this->clearCache();
        $this->loadFiles();

        $cacheDir = dirname($this->cacheFile);

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }

        try {
            $content = "<?php\n\nreturn " . var_export($this->config, true) . ";\n";
            file_put_contents($this->cacheFile, $content, LOCK_EX);
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf('Error saving configuration cache: %s', $e->getMessage()));
        }
    }

    public function clearCache(): void
    {
        $this->config = [];

        if (is_file($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadFromCache(): ?array
    {
        if (!file_exists($this->cacheFile)) {
            return null;
        }

        try {
            $cachedData = require $this->cacheFile;
        } catch (Throwable $e) {
            throw new RuntimeException(sprintf('Error loading configuration cache: %s', $e->getMessage()));
        }

        return is_array($cachedData) ? $cachedData : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function loadFromDirectory(): array
    {
        $config = [];

        $configFiles = array_values($this->findConfigurationFiles($this->configDirectory));

        foreach ($configFiles as $filename) {
            try {
                $key = pathinfo($filename, PATHINFO_FILENAME);
                $configFile = require $this->configDirectory . DIRECTORY_SEPARATOR . $filename;

                $config[$key] = $configFile;
            } catch (Throwable $e) {
                throw new RuntimeException(sprintf('Error loading configuration file %s: %s', $filename, $e->getMessage()));
            }
            
            continue;
        }

        return $config;
    }

    /**
     * @return array<string>
     */
    private function findConfigurationFiles(string $directory): array
    {
        return array_filter(scandir($directory), function (string $filename) {
            return pathinfo($filename, PATHINFO_EXTENSION) === 'php';
        });
    }
}
