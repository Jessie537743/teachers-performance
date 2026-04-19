<?php

use Illuminate\Support\Facades\Route;

/*
 * Super-admin dashboard routes — served on admin.localhost (or admin.<your-domain>).
 *
 * Phase 2 will add: super-admin login, tenants index, create wizard, suspend/resume.
 * For now this file is intentionally a stub so bootstrap/app.php can register it.
 */

Route::get('/', function () {
    return response('Super-admin dashboard — coming in Phase 2.', 200)
        ->header('Content-Type', 'text/plain');
})->name('admin.landing');
