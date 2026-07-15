<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Register custom middleware aliases
        $middleware->alias([
            'member'        => \App\Http\Middleware\MemberMiddleware::class,
            'lecturer'      => \App\Http\Middleware\LecturerMiddleware::class,
            'administrator' => \App\Http\Middleware\AdministratorMiddleware::class,
            'blacklist'     => \App\Http\Middleware\BlacklistMiddleware::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\BlacklistMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
