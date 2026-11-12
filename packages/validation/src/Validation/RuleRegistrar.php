<?php

declare(strict_types=1);

namespace Denosys\Validation;

use ReflectionClass;
use DirectoryIterator;
use Denosys\Validation\Rules\RuleInterface;

class RuleRegistrar
{
    /**
     * @param array<string, mixed> $cachedRules
      * @var array<string, object>
     */
    private static array $cachedRules = [];

    public function __construct(
        private readonly string $rulesDirectory,
        private readonly ?string $namespace = null
    ) {
    }

    public function register(): void
    {
        if (!empty(self::$cachedRules)) {
            foreach (self::$cachedRules as $ruleName => $factory) {
                Validator::extend($ruleName, $factory);
            }

            return;
        }

        $iterator = new DirectoryIterator($this->rulesDirectory);

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile() || 'php' !== $fileInfo->getExtension()) {
                continue;
            }

            $className = $fileInfo->getBasename('.php');
            $factory = rtrim($this->rulesNamespace(), '\\') . '\\' . $className;

            if (class_exists($factory)) {
                $reflection = new ReflectionClass($factory);

                if (!$reflection->isAbstract() && $reflection->implementsInterface(RuleInterface::class)) {
                    $ruleName = $factory::ruleName();
                    Validator::extend($ruleName, $factory);

                    self::$cachedRules[$ruleName] = $factory;
                }
            }
        }
    }

    private function rulesNamespace(): string
    {
        return $this->namespace ?? __NAMESPACE__ . '\\Rules';
    }
}
