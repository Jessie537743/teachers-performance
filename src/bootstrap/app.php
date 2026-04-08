<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway / proxied environments: trust forwarded headers so APP_URL,
        // HTTPS detection, and client IPs work correctly behind their edge.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role'                => \App\Http\Middleware\RoleMiddleware::class,
            'must.change.password' => \App\Http\Middleware\MustChangePassword::class,
            'dept.access'         => \App\Http\Middleware\EnsureDepartmentAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
