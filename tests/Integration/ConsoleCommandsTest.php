<?php

declare(strict_types=1);

use Denosys\Application;

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
        restore_error_handler();
        restore_exception_handler();
        $previous === null ? Application::resetInstance() : Application::setInstance($previous);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($base);
    }
});
