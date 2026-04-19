<?php

use Illuminate\Support\Facades\Route;

/*
 * Central-domain routes only — served on hosts listed in
 * config('tenancy.central_domains') (localhost, admin.localhost in dev).
 *
 * Each central domain gets its own GET / registration so the route
 * matcher prefers them over the unconstrained GET / declared inside
 * routes/tenant.php (Laravel prefers domain-constrained routes).
 *
 * All school-facing routes live in routes/tenant.php and are served
 * exclusively on tenant subdomains.
 */

$adminDomain = env('APP_ADMIN_DOMAIN', 'admin.localhost');

foreach (config('tenancy.central_domains', []) as $centralDomain) {
    // Skip the admin subdomain — it has its own route file (routes/admin.php)
    // registered with a domain constraint in bootstrap/app.php. Including it
    // here would shadow the admin routes since web.php is loaded first.
    if ($centralDomain === $adminDomain) {
        continue;
    }

    Route::domain($centralDomain)->get('/', fn () => response()->view('central.landing'))
        ->name('central.landing.' . str_replace('.', '_', $centralDomain));
}
