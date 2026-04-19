<?php

use App\Http\Controllers\SuperAdmin\AuthController;
use App\Http\Controllers\SuperAdmin\TenantController;
use Illuminate\Support\Facades\Route;

/*
 * Super-admin dashboard — served on admin.localhost (or admin.<your-domain>).
 * Domain constraint applied in bootstrap/app.php.
 */

Route::get('/login', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/login', [AuthController::class, 'login'])->name('admin.login.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

Route::middleware('auth:super_admin')->group(function () {
    Route::get('/', fn () => redirect()->route('admin.tenants.index'))->name('admin.landing');

    Route::get('/tenants', [TenantController::class, 'index'])->name('admin.tenants.index');
    Route::get('/tenants/create', [TenantController::class, 'create'])->name('admin.tenants.create');
    Route::post('/tenants', [TenantController::class, 'store'])->name('admin.tenants.store');
    Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('admin.tenants.show');
    Route::post('/tenants/{tenant}/suspend', [TenantController::class, 'suspend'])->name('admin.tenants.suspend');
    Route::post('/tenants/{tenant}/resume', [TenantController::class, 'resume'])->name('admin.tenants.resume');
    Route::post('/tenants/{tenant}/retry', [TenantController::class, 'retry'])->name('admin.tenants.retry');
});
