<?php

use Illuminate\Support\Facades\Route;

/*
 * Central-domain routes only — served on hosts listed in
 * config('tenancy.central_domains') (localhost, admin.localhost in dev).
 *
 * All school-facing routes live in routes/tenant.php and are served
 * exclusively on tenant subdomains.
 */

Route::get('/', function () {
    return response()->view('central.landing', [], 200);
})->name('central.landing');
