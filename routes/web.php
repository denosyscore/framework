<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use Denosys\Routing\RouteGroupInterface;
use Denosys\Routing\Router;

/** @var Router|RouteGroupInterface $router */

$router->get('/', [HomeController::class, 'index'])->name('home');
