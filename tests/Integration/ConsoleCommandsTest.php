<?php

declare(strict_types=1);

use Denosys\Application;

function consoleTestCleanup(string $base, ?Application $previous): void
{
    restore_error_handler();
    restore_exception_handler();
    $previous === null ? Application::resetInstance() : Application::setInstance($previous);
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($base);
}

it('lists console commands from the console package, the framework and package providers', function (): void {
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-console-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    $app = Application::configure(basePath: $base)->create();

    try {
        $commands = array_keys($app->console()->getCommands());

        expect($commands)->toContain(
            'make:controller',   // console package
            'config:cache',      // framework
            'migrate',           // database, via its provider
            'db:seed',
            'queue:work',        // queue, via its provider
        );
    } finally {
        consoleTestCleanup($base, $previous);
    }
});

it('discovers application commands under the application namespace', function (): void {
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-console-' . bin2hex(random_bytes(6));
    $namespace = 'ConsoleNs' . bin2hex(random_bytes(4));
    mkdir($base . '/config', 0700, true);
    mkdir($base . '/app/Console/Commands', 0700, true);
    file_put_contents($base . '/composer.json', json_encode(['autoload' => ['psr-4' => [$namespace . '\\' => 'app/']]]));
    $file = $base . '/app/Console/Commands/Greet.php';
    file_put_contents($file, <<<PHP
        <?php
        namespace {$namespace}\\Console\\Commands;
        #[\\Denosys\\Console\\AsCommand('custom:greet')]
        final class Greet { public function __invoke(): int { return 0; } }
        PHP);
    require $file;
    $app = Application::configure(basePath: $base)->create();

    try {
        expect(array_keys($app->console()->getCommands()))->toContain('custom:greet');
    } finally {
        consoleTestCleanup($base, $previous);
    }
});

it('falls back to the App namespace when the application namespace cannot be detected', function (): void {
    $previous = Application::getInstance();
    $base = sys_get_temp_dir() . '/denosys-console-' . bin2hex(random_bytes(6));
    mkdir($base . '/config', 0700, true);
    $app = Application::configure(basePath: $base)->create();

    try {
        expect(array_keys($app->console()->getCommands()))->toContain('make:controller');
    } finally {
        consoleTestCleanup($base, $previous);
    }
});
