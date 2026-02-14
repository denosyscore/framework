<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Laminas\Diactoros\Response\HtmlResponse;

final class HomeController
{
    public function index(): HtmlResponse
    {
        return new HtmlResponse('<h1>DenoSys Framework</h1><p>It works.</p>');
    }
}
