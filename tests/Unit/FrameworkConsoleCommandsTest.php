<?php

declare(strict_types=1);

use Denosys\Application;
use Denosys\Console\CommandInterface;

it('registers the framework-owned console commands with the console kernel', function (): void {
    $names = array_map(
        static fn (string $class): string => (new ReflectionClass($class))->newInstanceWithoutConstructor()->getName(),
        Application::consoleCommands(),
    );

    expect($names)->toContain('config:cache', 'config:clear', 'routes:cache', 'routes:clear', 'view:clear');
    foreach (Application::consoleCommands() as $class) {
        expect(is_subclass_of($class, CommandInterface::class))->toBeTrue()
            ->and($class)->toStartWith('Denosys\\Framework\\Console\\');
    }
});
