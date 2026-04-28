<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Tenant routes — served on any subdomain not in central_domains.
            // Subdomain matching + tenancy initialization is done by the
            // InitializeTenancyBySubdomain middleware inside routes/tenant.php.
            Route::middleware('web')
                ->group(base_path('routes/tenant.php'));

            // Super-admin dashboard — restricted to admin.* central domain.
            // Domain comes from APP_ADMIN_DOMAIN env var (default: admin.localhost).
            Route::middleware('web')
                ->domain(env('APP_ADMIN_DOMAIN', 'admin.localhost'))
                ->group(base_path('routes/admin.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role'                 => \App\Http\Middleware\RoleMiddleware::class,
            'must.change.password' => \App\Http\Middleware\MustChangePassword::class,
            'dept.access'          => \App\Http\Middleware\EnsureDepartmentAccess::class,
            'tenant'               => \Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain::class,
            'tenant.active'        => \App\Http\Middleware\EnsureTenantIsActive::class,
            'plan.feature'         => \App\Http\Middleware\EnsurePlanFeature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
