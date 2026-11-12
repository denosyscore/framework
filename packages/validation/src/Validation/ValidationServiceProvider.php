<?php

declare(strict_types=1);

namespace Denosys\Validation;

use Denosys\Container\ContainerInterface;
use Denosys\Database\Connection\Connection;
use Denosys\Contracts\ServiceProviderInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Throwable;

/**
 * Service provider for validation services
 */
class ValidationServiceProvider implements ServiceProviderInterface
{
    public function register(ContainerInterface $container): void
    {
        $ruleRegistrar = new RuleRegistrar(
            __DIR__ . DIRECTORY_SEPARATOR . 'Rules',
        );
        $ruleRegistrar->register();

    }

    public function boot(ContainerInterface $container, ?EventDispatcherInterface $dispatcher = null): void
    {
        // Register database-dependent rules with connection injection
        $this->registerDatabaseRules($container);
    }

    /**
     * Register validation rules that require database connection
     */
    private function registerDatabaseRules(ContainerInterface $container): void
    {
        // Register lazy factories to avoid resolving DB during application boot.
        Validator::extend('unique', function () use ($container) {
            $rule = new Rules\Unique();
            $this->attachConnectionIfAvailable($container, $rule);
            return $rule;
        });

        Validator::extend('exists', function () use ($container) {
            $rule = new Rules\Exists();
            $this->attachConnectionIfAvailable($container, $rule);
            return $rule;
        });
    }

    private function attachConnectionIfAvailable(ContainerInterface $container, Rules\Unique|Rules\Exists $rule): void
    {
        foreach ([Connection::class, 'db'] as $binding) {
            if (!$container->has($binding)) {
                continue;
            }

            try {
                $connection = $container->get($binding);
            } catch (Throwable) {
                continue;
            }

            if ($connection instanceof Connection) {
                $rule->setConnection($connection);
                return;
            }
        }
    }

    /**
     * Register custom validation rules
     */
    private function registerCustomRules(): void
    {
        // Add any custom rules here
        // Validator::extend('custom_rule', CustomRule::class);
    }

    /**
     * Register view helpers for validation
     */
//    private function registerViewHelpers(ContainerInterface $container): void
//    {
//        $viewEngine = $container->get('view');
//
//        if (method_exists($viewEngine, 'directive')) {
//            // Register @error directive
//            $viewEngine->directive('error', function (string $expression): string {
/*                return "<?php if(isset(\$errors) && \$errors->has({$expression})): ?>";*/
//            });
//
//            $viewEngine->directive('enderror', function (): string {
/*                return "<?php endif; ?>";*/
//            });
//
//            // Register @errors directive for all errors
//            $viewEngine->directive('errors', function (): string {
/*                return "<?php if(isset(\$errors) && count(\$errors->all()) > 0): ?>";*/
//            });
//
//            $viewEngine->directive('enderrors', function (): string {
/*                return "<?php endif; ?>";*/
//            });
//        }
//    }
}
