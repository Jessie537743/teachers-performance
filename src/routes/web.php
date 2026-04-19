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

foreach (config('tenancy.central_domains', []) as $centralDomain) {
    Route::domain($centralDomain)->get('/', fn () => response()->view('central.landing'))
        ->name('central.landing.' . str_replace('.', '_', $centralDomain));
}
