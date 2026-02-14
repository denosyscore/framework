<?php

declare(strict_types=1);

use Denosys\Http\Exceptions\ExceptionHandler;

return (new Denosys\Application(basePath: dirname(__DIR__)))
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        Denosys\Filesystem\FilesystemServiceProvider::class,
        Denosys\Queue\QueueServiceProvider::class,
    ])
    ->withRoutes(web: __DIR__ . '/../routes/web.php')
    ->withExceptions(function (ExceptionHandler $exception) {
        // Register custom exception mappings here.
    });
