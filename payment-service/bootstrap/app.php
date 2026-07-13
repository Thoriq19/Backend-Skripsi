<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Manual class loader fallback for Shared namespace
spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'Shared\\')) {
        $path = __DIR__ . '/../../shared/' . str_replace('\\', '/', substr($class, 7)) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
});

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
