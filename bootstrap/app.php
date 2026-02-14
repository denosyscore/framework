<?php

declare(strict_types=1);

use CFXP\Core\Exceptions\ExceptionHandler;

return (new CFXP\Core\Application(basePath: dirname(__DIR__)))
    ->withProviders([
        App\Providers\AppServiceProvider::class,
        CFXP\Core\Filesystem\FilesystemServiceProvider::class,
        CFXP\Core\Queue\QueueServiceProvider::class,
    ])
    ->withRoutes(web: __DIR__ . '/../routes/web.php')
    ->withExceptions(function (ExceptionHandler $exception) {
        // Register custom exception mappings here.
    });
