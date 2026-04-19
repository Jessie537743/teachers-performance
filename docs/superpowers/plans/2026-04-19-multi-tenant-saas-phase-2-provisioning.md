# Multi-Tenant SaaS — Phase 2: Super-Admin Provisioning Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the super-admin dashboard at `admin.localhost:8081` that lets a platform operator log in, view all schools, create a new school via a wizard (which provisions a fresh tenant DB + runs migrations + seeds a blank-school template + creates the first admin user), and suspend/resume schools.

**Architecture:** Layer a separate `super_admin` auth guard (central DB) and an `admin.*` route domain on top of the Phase 1 foundation. A single `ProvisionTenantJob` runs the 6-step provisioning workflow synchronously in-request (capstone-acceptable; queueing is future work). Suspended tenants are blocked at the tenancy middleware via a new `EnsureTenantIsActive` middleware. The blank-school template is a Laravel seeder that re-uses the existing system-primitive seeders (criteria, questions, permissions, lexicons, intervention catalog) and skips per-school data.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL 8, `stancl/tenancy` v3.10, Tailwind CSS (already in project), PHPUnit 12.

**Out of scope for Phase 2 (handled in Phase 3 or future):**
- ML API tenant-awareness — Phase 3.
- Cross-tenant leakage feature test — Phase 3 (needs ≥ 2 tenants, which Phase 2 produces).
- Public self-service signup, billing, plans, email delivery, custom domains, tenant deletion UI, theme customization — explicitly deferred per spec.

---

## File Structure

**New files:**

| Path | Responsibility |
|---|---|
| `src/app/Http/Controllers/SuperAdmin/AuthController.php` | login form GET, login POST, logout |
| `src/app/Http/Controllers/SuperAdmin/TenantController.php` | tenants index, show, create form, store, suspend, resume |
| `src/app/Http/Middleware/EnsureTenantIsActive.php` | blocks logins on suspended tenants (returns 403 page) |
| `src/app/Jobs/ProvisionTenantJob.php` | the 6-step provisioning workflow |
| `src/app/Rules/AvailableSubdomain.php` | validates subdomain (allowed chars, reserved-words blocklist, uniqueness) |
| `src/database/seeders/TenantTemplateSeeder.php` | seeds blank-school primitives into a freshly provisioned tenant |
| `src/resources/views/super-admin/layout.blade.php` | shared layout for all super-admin pages |
| `src/resources/views/super-admin/auth/login.blade.php` | super-admin login form |
| `src/resources/views/super-admin/tenants/index.blade.php` | tenants table + actions |
| `src/resources/views/super-admin/tenants/create.blade.php` | wizard form |
| `src/resources/views/super-admin/tenants/show.blade.php` | tenant detail + provisioning log + suspend/resume buttons |
| `src/resources/views/super-admin/tenants/created.blade.php` | success page after creating a tenant (shows temp password ONCE) |
| `src/resources/views/tenancy/suspended.blade.php` | shown when a user hits a suspended tenant |
| `src/tests/Feature/SuperAdmin/AuthTest.php` | super-admin can log in and reach dashboard |
| `src/tests/Feature/SuperAdmin/ProvisionTenantTest.php` | end-to-end provisioning flow |

**Modified files:**

| Path | Change |
|---|---|
| `src/config/auth.php` | add `super_admin` guard + `super_admins` provider |
| `src/.env` and `src/.env.example` | switch `SESSION_DRIVER=database` → `SESSION_DRIVER=file` (sessions table only exists in tenant DBs; super-admin context has no DB-backed session storage otherwise) |
| `src/routes/admin.php` | replace placeholder with login + tenant CRUD routes |
| `src/routes/tenant.php` | add `EnsureTenantIsActive` middleware to the existing tenancy group |
| `src/database/seeders/CentralSeeder.php` | (optional) update super-admin seed to remind operator to change password |

**Files explicitly NOT touched in Phase 2:**

- Existing tenant controllers/models/views — unchanged. The `User` model, `Department`, `Announcement`, etc. all stay the same.
- ML API — Phase 3.
- Existing seeders (`CriteriaSeeder`, `QuestionSeeder`, etc.) — re-used as-is by `TenantTemplateSeeder`.

---

## Pre-Phase Notes (read before Task 1)

**Session driver change rationale:** Phase 1 didn't touch sessions, but the super-admin context has no `sessions` table because that table only lives in the tenant DBs (where the existing `users` migration created it). Two options: add a sessions table to central, or switch to file sessions globally. We pick file because (a) it's one env var change, (b) stancl's filesystem tenancy bootstrapper already namespaces file storage per-tenant so tenant sessions remain isolated, and (c) capstone-scale traffic doesn't need DB sessions.

**Provisioning sync vs. queued:** For the capstone, the provisioning job runs synchronously inside the form submit request. The whole flow takes ~5–15s on a fast machine. The user sees a loading state, then the success page. Acceptable for a demo. Switching to queued (Bus::dispatch with a polling page) is future work — keeping it sync removes the need for a running queue worker during the demo.

**Where the temp password goes:** The wizard form collects the first admin's name + email. The job generates a random temp password, creates the user with `must_change_password=true`, and the success page shows the password ONCE. No email is sent. Operator copies it and shares with the school admin out-of-band.

**Subdomain validation:** Lower-case letters, digits, hyphens; not starting/ending with hyphen; 2–32 chars; not in the reserved list (`admin`, `www`, `api`, `app`, `mail`, `ftp`, `cdn`); not already taken in the `tenants.subdomain` column.

---

## Task 1: Add `super_admin` auth guard + provider

**Files:**
- Modify: `src/config/auth.php`

- [ ] **Step 1: Modify `src/config/auth.php`**

Inside `'guards' => [...]`, after the existing `'web'` guard, add:

```php
        'super_admin' => [
            'driver' => 'session',
            'provider' => 'super_admins',
        ],
```

Inside `'providers' => [...]`, after the existing `'users'` provider, add:

```php
        'super_admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\SuperAdmin::class,
        ],
```

- [ ] **Step 2: Verify in tinker**

```bash
docker exec tp-app php artisan config:clear
docker exec tp-app php artisan tinker --execute="
echo config('auth.guards.super_admin.provider') . PHP_EOL;
echo config('auth.providers.super_admins.model') . PHP_EOL;
"
```

Expected output:
```
super_admins
App\Models\SuperAdmin
```

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/config/auth.php
git commit -m "feat(super-admin): add auth guard + provider for super_admins"
```

---

## Task 2: Switch session driver to file

**Files:**
- Modify: `src/.env` (gitignored, runtime only)
- Modify: `src/.env.example` (committed)
- Modify: `src/docker-compose.yml` if `SESSION_DRIVER` is set there

- [ ] **Step 1: Check where SESSION_DRIVER is configured**

```bash
grep -rn "SESSION_DRIVER" src/.env src/.env.example docker-compose.yml docker-compose.override.yml 2>/dev/null
```

- [ ] **Step 2: Change `SESSION_DRIVER=database` to `SESSION_DRIVER=file` everywhere it appears**

Use `Edit` tool on each file: replace `SESSION_DRIVER=database` with `SESSION_DRIVER=file`. The docker-compose service env block IS authoritative for the running container, so it must change.

- [ ] **Step 3: Restart and verify the existing app still works**

```bash
docker compose restart app
docker exec tp-app php artisan config:clear
curl -sS -o /dev/null -w "HTTP %{http_code}\n" http://jcd.localhost:8081/login
```

Expected: 200. (The login page still renders. Sessions now go to `storage/framework/sessions/` instead of the DB.)

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add docker-compose.yml src/.env.example
git commit -m "feat(tenancy): switch session driver to file

Super-admin context has no sessions table (that lives in tenant DBs only).
File sessions work for both super-admin and tenants — stancl's filesystem
bootstrapper namespaces tenant session paths so isolation is preserved."
```

---

## Task 3: Super-admin Blade layout

**Files:**
- Create: `src/resources/views/super-admin/layout.blade.php`

- [ ] **Step 1: Create the layout**

```php
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Super Admin' }} — Platform Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex flex-col">
    @auth('super_admin')
    <header class="bg-slate-900 text-white">
        <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between">
            <a href="{{ route('admin.tenants.index') }}" class="font-semibold tracking-tight">Platform Console</a>
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('admin.tenants.index') }}" class="hover:text-slate-300">Schools</a>
                <span class="text-slate-400">{{ auth('super_admin')->user()->email }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-300 hover:text-white">Sign out</button>
                </form>
            </nav>
        </div>
    </header>
    @endauth

    <main class="flex-1">
        <div class="max-w-6xl mx-auto px-6 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>
</body>
</html>
```

(Tailwind via CDN keeps this lightweight without adding a build step. The existing app uses Vite + Tailwind, but the super-admin UI is small enough that the CDN is acceptable.)

- [ ] **Step 2: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/resources/views/super-admin/layout.blade.php
git commit -m "feat(super-admin): shared Blade layout"
```

---

## Task 4: Super-admin AuthController + login view + routes

**Files:**
- Create: `src/app/Http/Controllers/SuperAdmin/AuthController.php`
- Create: `src/resources/views/super-admin/auth/login.blade.php`
- Modify: `src/routes/admin.php` (replace placeholder)

- [ ] **Step 1: Write `src/app/Http/Controllers/SuperAdmin/AuthController.php`**

```php
<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('super-admin.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('super_admin')->attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.tenants.index'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('super_admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
```

- [ ] **Step 2: Write `src/resources/views/super-admin/auth/login.blade.php`**

```php
@extends('super-admin.layout', ['title' => 'Sign in'])

@section('content')
<div class="max-w-md mx-auto bg-white shadow rounded-lg p-8 mt-12">
    <h1 class="text-2xl font-semibold text-slate-900 mb-1">Platform Console</h1>
    <p class="text-sm text-slate-500 mb-6">Sign in to manage schools.</p>

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input id="password" name="password" type="password" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
        </div>
        @error('email')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="w-full rounded-md bg-slate-900 text-white py-2 hover:bg-slate-800">
            Sign in
        </button>
    </form>
</div>
@endsection
```

- [ ] **Step 3: Replace `src/routes/admin.php` with the routed version**

```php
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
```

(`TenantController` and its methods are built in Tasks 5/8/9. The routes referencing them parse fine — Laravel only resolves the controller class lazily on dispatch.)

- [ ] **Step 4: Verify the login page renders**

```bash
docker exec tp-app php artisan route:clear
curl -sS -o /dev/null -w "HTTP %{http_code}\n" http://admin.localhost:8081/login
```

Expected: 200. (You may also visit `http://admin.localhost:8081/login` in a browser to verify the Tailwind styling renders. You will see a "TenantController not found" error if you try logging in — that's expected, fixed in Task 5.)

- [ ] **Step 5: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Http/Controllers/SuperAdmin/AuthController.php src/resources/views/super-admin/auth/login.blade.php src/routes/admin.php
git commit -m "feat(super-admin): login form + auth controller + routes"
```

---

## Task 5: TenantController stub + index page

**Files:**
- Create: `src/app/Http/Controllers/SuperAdmin/TenantController.php` (stub with `index()` only; other methods come in later tasks)
- Create: `src/resources/views/super-admin/tenants/index.blade.php`

- [ ] **Step 1: Write `src/app/Http/Controllers/SuperAdmin/TenantController.php`**

```php
<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::orderByDesc('id')->get();

        return view('super-admin.tenants.index', ['tenants' => $tenants]);
    }
}
```

(Other methods — `create`, `store`, `show`, `suspend`, `resume`, `retry` — added in later tasks. Routes pointing at them will 500 until then; that's fine because the index page is what we're testing now.)

- [ ] **Step 2: Write `src/resources/views/super-admin/tenants/index.blade.php`**

```php
@extends('super-admin.layout', ['title' => 'Schools'])

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Schools</h1>
        <p class="text-sm text-slate-500">{{ $tenants->count() }} {{ Str::plural('school', $tenants->count()) }} registered.</p>
    </div>
    <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        New school
    </a>
</div>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Name</th>
                <th class="px-6 py-3">Subdomain</th>
                <th class="px-6 py-3">Database</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Created</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-200 text-sm text-slate-700">
            @forelse ($tenants as $tenant)
                <tr>
                    <td class="px-6 py-3 font-medium text-slate-900">{{ $tenant->name }}</td>
                    <td class="px-6 py-3"><code class="text-xs">{{ $tenant->subdomain }}</code></td>
                    <td class="px-6 py-3"><code class="text-xs">{{ $tenant->getAttribute('database') }}</code></td>
                    <td class="px-6 py-3">
                        @php
                            $color = match($tenant->status) {
                                'active' => 'bg-green-100 text-green-800',
                                'provisioning' => 'bg-yellow-100 text-yellow-800',
                                'suspended' => 'bg-slate-200 text-slate-700',
                                'failed' => 'bg-red-100 text-red-800',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">{{ $tenant->status }}</span>
                    </td>
                    <td class="px-6 py-3 text-slate-500">{{ $tenant->created_at?->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-slate-700 hover:text-slate-900">View →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No schools yet. Create the first one.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
```

- [ ] **Step 3: Verify access while logged out + logged in**

Logged out:
```bash
curl -sS -o /dev/null -w "HTTP %{http_code} | redirect=%{redirect_url}\n" http://admin.localhost:8081/tenants
```
Expected: 302 redirect to `/login`.

Logged in (via tinker — bypass the login form):
```bash
docker exec tp-app php artisan tinker --execute="
session()->put('login_super_admin_' . sha1(\Illuminate\Auth\SessionGuard::class), 1);
echo 'tenants count: ' . App\Models\Tenant::count() . PHP_EOL;
"
```
Expected: `tenants count: 1` (the JCD tenant).

The actual logged-in browser test happens in Task 12 (smoke test).

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Http/Controllers/SuperAdmin/TenantController.php src/resources/views/super-admin/tenants/index.blade.php
git commit -m "feat(super-admin): TenantController stub + tenants index page"
```

---

## Task 6: TenantTemplateSeeder

**Files:**
- Create: `src/database/seeders/TenantTemplateSeeder.php`

- [ ] **Step 1: Write the seeder**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Runs against a freshly provisioned tenant DB to seed the system primitives
 * a school needs to function. Per-school data (departments, faculty, students,
 * subjects) is intentionally NOT seeded — the school admin populates those
 * after first login.
 */
class TenantTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            AnnouncementPermissionsSeeder::class,
            CriteriaSeeder::class,
            QuestionSeeder::class,
            DeanRecommendationCriterionSeeder::class,
            AcademicAdministratorsCriteriaSeeder::class,
            InterventionSeeder::class,
            SentimentLexiconSeeder::class,
        ]);
    }
}
```

(Per-school seeders skipped: `DepartmentSeeder`, `DefaultUserSeeder`, `FacultySeeder`, `StudentSeeder`, `CourseSeeder`, `SubjectSeeder`, `SubjectAssignmentSeeder`, `SampleEvaluationDataSeeder`. The first admin user is created by `ProvisionTenantJob` directly with operator-supplied credentials.)

- [ ] **Step 2: Verify the seeder is callable**

```bash
docker exec tp-app composer dump-autoload
docker exec tp-app php artisan tinker --execute="echo Database\Seeders\TenantTemplateSeeder::class;"
```

Expected: `Database\Seeders\TenantTemplateSeeder`. (We don't actually run it here — it's invoked from inside the provisioning job in Task 7.)

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/database/seeders/TenantTemplateSeeder.php
git commit -m "feat(super-admin): TenantTemplateSeeder for blank-school primitives"
```

---

## Task 7: ProvisionTenantJob

**Files:**
- Create: `src/app/Jobs/ProvisionTenantJob.php`

The job runs synchronously inside the form-submit request (capstone scope). The 6 steps are: insert tenant row, create DB, run tenant migrations, run TenantTemplateSeeder, create first admin user, mark tenant active. On failure, mark tenant `failed` and persist the error in `tenant_provisioning_jobs`.

- [ ] **Step 1: Write the job**

```php
<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantProvisioningJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\TenantDatabaseManagers\MySQLDatabaseManager;
use Throwable;

class ProvisionTenantJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $adminName,
        public string $adminEmail,
        public string $adminTempPassword,
    ) {}

    public function handle(): void
    {
        $audit = TenantProvisioningJob::create([
            'tenant_id'  => $this->tenant->id,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $this->createDatabase();
            $this->runTenantMigrations();
            $this->seedTemplate();
            $this->createFirstAdmin();

            $this->tenant->update(['status' => 'active']);

            $audit->update([
                'status'      => 'succeeded',
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Tenant provisioning failed', [
                'tenant_id' => $this->tenant->id,
                'error'     => $e->getMessage(),
            ]);

            $this->tenant->update(['status' => 'failed']);

            $audit->update([
                'status'      => 'failed',
                'error'       => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    protected function createDatabase(): void
    {
        $databaseName = $this->tenant->getAttribute('database');

        // Connect to MySQL on the central connection's host but without a
        // selected database so we can issue CREATE DATABASE.
        $centralConfig = config('database.connections.central');
        config(['database.connections._tenant_provisioner' => array_merge($centralConfig, ['database' => null])]);
        DB::purge('_tenant_provisioner');

        DB::connection('_tenant_provisioner')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // Grant the tenant connection's user access to the new DB. The MySQL
        // user only auto-receives access to MYSQL_DATABASE; subsequent DBs
        // need an explicit GRANT.
        $tenantUser = config('database.connections.mysql.username');
        DB::connection('_tenant_provisioner')->statement(
            "GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$tenantUser}'@'%'"
        );
        DB::connection('_tenant_provisioner')->statement('FLUSH PRIVILEGES');
    }

    protected function runTenantMigrations(): void
    {
        // Stancl exposes a `tenants:migrate` command that initializes
        // tenancy, runs migrations against the tenant DB using the
        // migration_parameters config from tenancy.php, and tears down.
        \Artisan::call('tenants:migrate', [
            '--tenants' => [(string) $this->tenant->id],
            '--force'   => true,
        ]);
    }

    protected function seedTemplate(): void
    {
        \Artisan::call('tenants:seed', [
            '--tenants' => [(string) $this->tenant->id],
            '--class'   => 'Database\\Seeders\\TenantTemplateSeeder',
            '--force'   => true,
        ]);
    }

    protected function createFirstAdmin(): void
    {
        tenancy()->initialize($this->tenant);

        try {
            // While tenancy is initialized, the default `mysql` connection
            // points at the tenant DB. Use the model so password hashing,
            // casts, and role serialization stay consistent with the rest
            // of the app.
            \App\Models\User::create([
                'name'                 => $this->adminName,
                'email'                => $this->adminEmail,
                'password'             => $this->adminTempPassword, // 'hashed' cast
                'roles'                => ['admin'],                // 'array' cast
                'is_active'            => true,
                'must_change_password' => true,
            ]);
        } finally {
            tenancy()->end();
        }
    }
}
```

**Notes:**
- The `_tenant_provisioner` is a one-shot connection used only for CREATE DATABASE / GRANT. We can't use the `central` connection because it has a selected database; CREATE DATABASE doesn't require one but is cleaner without.
- `tenancy()->initialize($tenant)` swaps the default DB connection (or rather, the `tenant` connection — stancl's MySQLDatabaseManager registers it) to the tenant DB for the duration of the call. We always wrap in try/finally with `tenancy()->end()` to avoid leaking tenant context if a step throws.
- The migrate command uses `--database=tenant` because stancl exposes the active tenant DB under the `tenant` connection name during initialization.
- `must_change_password=true` forces the first admin to set their real password on first login. The existing `MustChangePassword` middleware (already in the project) handles this.

- [ ] **Step 2: Verify the job class loads**

```bash
docker exec tp-app composer dump-autoload
docker exec tp-app php artisan tinker --execute="echo App\Jobs\ProvisionTenantJob::class;"
```

Expected: `App\Jobs\ProvisionTenantJob`.

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Jobs/ProvisionTenantJob.php
git commit -m "feat(super-admin): ProvisionTenantJob — 6-step provisioning workflow"
```

---

## Task 8: Subdomain validation rule + create form + store action

**Files:**
- Create: `src/app/Rules/AvailableSubdomain.php`
- Create: `src/resources/views/super-admin/tenants/create.blade.php`
- Create: `src/resources/views/super-admin/tenants/created.blade.php`
- Modify: `src/app/Http/Controllers/SuperAdmin/TenantController.php` — add `create()`, `store()` methods

- [ ] **Step 1: Write the validation rule**

`src/app/Rules/AvailableSubdomain.php`:

```php
<?php

namespace App\Rules;

use App\Models\Tenant;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class AvailableSubdomain implements ValidationRule
{
    private const RESERVED = ['admin', 'www', 'api', 'app', 'mail', 'ftp', 'cdn', 'assets', 'static'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');
            return;
        }

        $value = strtolower($value);

        if (! preg_match('/^[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?$/', $value)) {
            $fail('The :attribute must be 2-32 chars, lowercase letters/digits/hyphens, not starting or ending with a hyphen.');
            return;
        }

        if (in_array($value, self::RESERVED, true)) {
            $fail("The subdomain ':input' is reserved and cannot be used.");
            return;
        }

        if (Tenant::where('subdomain', $value)->exists()) {
            $fail("The subdomain ':input' is already taken.");
        }
    }
}
```

- [ ] **Step 2: Write the create form view**

`src/resources/views/super-admin/tenants/create.blade.php`:

```php
@extends('super-admin.layout', ['title' => 'New school'])

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-semibold text-slate-900 mb-1">Provision a new school</h1>
    <p class="text-sm text-slate-500 mb-6">This creates a fresh tenant database, runs migrations, seeds the blank-school template, and creates the first admin user.</p>

    <form method="POST" action="{{ route('admin.tenants.store') }}" class="bg-white shadow rounded-lg p-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">School name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="120"
                placeholder="e.g. St. Mary's Academy"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="subdomain" class="block text-sm font-medium text-slate-700 mb-1">Subdomain</label>
            <div class="flex items-center">
                <input id="subdomain" name="subdomain" type="text" value="{{ old('subdomain') }}" required
                    pattern="[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?"
                    placeholder="stmarys"
                    class="flex-1 rounded-l-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <span class="inline-flex items-center rounded-r-md border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">.{{ str_replace('admin.', '', env('APP_ADMIN_DOMAIN', 'admin.localhost')) }}:8081</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">2-32 chars, lowercase letters / digits / hyphens. Reserved: admin, www, api, app, mail, ftp, cdn.</p>
            @error('subdomain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <hr class="border-slate-200">

        <div>
            <label for="admin_name" class="block text-sm font-medium text-slate-700 mb-1">First admin — name</label>
            <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required maxlength="120"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            @error('admin_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-1">First admin — email</label>
            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <p class="mt-1 text-xs text-slate-500">A temporary password will be generated and shown once.</p>
            @error('admin_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Provision school
            </button>
        </div>
    </form>
</div>
@endsection
```

- [ ] **Step 3: Write the created/success view**

`src/resources/views/super-admin/tenants/created.blade.php`:

```php
@extends('super-admin.layout', ['title' => 'School created'])

@section('content')
<div class="max-w-xl bg-white shadow rounded-lg p-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-lg">✓</div>
        <h1 class="text-xl font-semibold text-slate-900">{{ $tenant->name }} is ready.</h1>
    </div>

    <dl class="space-y-3 text-sm mb-6">
        <div>
            <dt class="text-slate-500">School URL</dt>
            <dd class="text-slate-900 font-mono">
                <a href="http://{{ $tenant->subdomain }}.localhost:8081" class="underline hover:text-slate-700" target="_blank">
                    http://{{ $tenant->subdomain }}.localhost:8081
                </a>
            </dd>
        </div>
        <div>
            <dt class="text-slate-500">Admin email</dt>
            <dd class="text-slate-900 font-mono">{{ $adminEmail }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Temporary password (shown once)</dt>
            <dd class="text-slate-900 font-mono select-all bg-yellow-50 border border-yellow-200 rounded px-3 py-2">{{ $tempPassword }}</dd>
        </div>
    </dl>

    <p class="text-sm text-slate-500 mb-6">Send these credentials to the school admin. They will be required to change the password on first login.</p>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to schools</a>
        <a href="http://{{ $tenant->subdomain }}.localhost:8081" target="_blank" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Open school dashboard
        </a>
    </div>
</div>
@endsection
```

- [ ] **Step 4: Add `create()` and `store()` to TenantController**

Replace the contents of `src/app/Http/Controllers/SuperAdmin/TenantController.php` with:

```php
<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantJob;
use App\Models\Tenant;
use App\Rules\AvailableSubdomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::orderByDesc('id')->get();

        return view('super-admin.tenants.index', ['tenants' => $tenants]);
    }

    public function create(): View
    {
        return view('super-admin.tenants.create');
    }

    public function store(Request $request): RedirectResponse|View
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'subdomain'   => ['required', 'string', new AvailableSubdomain()],
            'admin_name'  => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
        ]);

        $tenant = Tenant::create([
            'name'      => $data['name'],
            'subdomain' => strtolower($data['subdomain']),
            'database'  => 'tenant_' . Str::random(8),
            'status'    => 'provisioning',
        ]);

        // Mirror the subdomain into the central `domains` table so the
        // stancl resolver can find it.
        $tenant->domains()->create(['domain' => strtolower($data['subdomain'])]);

        // Resolve the actual database name now that the tenant has an id.
        // Convention: tenant_<id> for new tenants (the random suffix above
        // was a placeholder to satisfy the NOT NULL constraint on insert).
        $tenant->update(['database' => 'tenant_' . $tenant->id]);
        $tenant->refresh();

        $tempPassword = Str::random(12);

        try {
            (new ProvisionTenantJob(
                tenant: $tenant,
                adminName: $data['admin_name'],
                adminEmail: $data['admin_email'],
                adminTempPassword: $tempPassword,
            ))->handle();
        } catch (\Throwable $e) {
            // Job has already marked tenant 'failed' and recorded the error.
            return redirect()
                ->route('admin.tenants.show', $tenant)
                ->with('error', 'Provisioning failed: ' . $e->getMessage());
        }

        return view('super-admin.tenants.created', [
            'tenant'       => $tenant->refresh(),
            'adminEmail'   => $data['admin_email'],
            'tempPassword' => $tempPassword,
        ]);
    }
}
```

- [ ] **Step 5: Verify validation rejects bad subdomains**

```bash
docker exec tp-app php artisan tinker --execute="
\$rule = new App\Rules\AvailableSubdomain();
\$rule->validate('subdomain', 'admin', function(\$msg) { echo 'admin → ' . \$msg . PHP_EOL; });
\$rule->validate('subdomain', 'jcd', function(\$msg) { echo 'jcd → ' . \$msg . PHP_EOL; });
\$rule->validate('subdomain', 'A-Bad', function(\$msg) { echo 'A-Bad → ' . \$msg . PHP_EOL; });
\$rule->validate('subdomain', 'demo', function(\$msg) { echo 'demo → no message means OK' . PHP_EOL; });
"
```

Expected output (errors for first three, silent for `demo`):
```
admin → The subdomain 'admin' is reserved and cannot be used.
jcd → The subdomain 'jcd' is already taken.
A-Bad → The :attribute must be 2-32 chars, ...
demo → no message means OK
```

(Validators in Laravel rules use `:input` and `:attribute` placeholders that are normally filled by the validator — in tinker we see them literal. That's fine, the actual form rendering substitutes them.)

- [ ] **Step 6: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Rules/AvailableSubdomain.php src/resources/views/super-admin/tenants/create.blade.php src/resources/views/super-admin/tenants/created.blade.php src/app/Http/Controllers/SuperAdmin/TenantController.php
git commit -m "feat(super-admin): create-tenant wizard form + ProvisionTenantJob dispatch"
```

---

## Task 9: Tenant detail page + suspend / resume / retry

**Files:**
- Create: `src/resources/views/super-admin/tenants/show.blade.php`
- Modify: `src/app/Http/Controllers/SuperAdmin/TenantController.php` — add `show`, `suspend`, `resume`, `retry`

- [ ] **Step 1: Write the show view**

`src/resources/views/super-admin/tenants/show.blade.php`:

```php
@extends('super-admin.layout', ['title' => $tenant->name])

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← All schools</a>
</div>

<div class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $tenant->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                <a href="http://{{ $tenant->subdomain }}.localhost:8081" target="_blank" class="text-slate-700 hover:text-slate-900 underline">
                    {{ $tenant->subdomain }}.localhost:8081
                </a>
            </p>
        </div>
        @php
            $color = match($tenant->status) {
                'active' => 'bg-green-100 text-green-800',
                'provisioning' => 'bg-yellow-100 text-yellow-800',
                'suspended' => 'bg-slate-200 text-slate-700',
                'failed' => 'bg-red-100 text-red-800',
            };
        @endphp
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $color }}">{{ $tenant->status }}</span>
    </div>

    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-slate-500">Database</dt>
            <dd class="font-mono text-slate-900">{{ $tenant->getAttribute('database') }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Created</dt>
            <dd class="text-slate-900">{{ $tenant->created_at?->toDayDateTimeString() }}</dd>
        </div>
    </dl>
</div>

@if ($jobs->isNotEmpty())
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Provisioning history</h2>
    <ul class="divide-y divide-slate-200">
        @foreach ($jobs as $job)
            <li class="py-2 text-sm flex items-start justify-between gap-4">
                <div>
                    <span class="font-medium text-slate-900">{{ ucfirst($job->status) }}</span>
                    <span class="text-slate-500"> — {{ $job->created_at->toDayDateTimeString() }}</span>
                    @if ($job->error)
                        <pre class="mt-1 bg-red-50 border border-red-200 rounded p-2 text-xs text-red-700 whitespace-pre-wrap">{{ $job->error }}</pre>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Actions</h2>
    <div class="flex flex-wrap items-center gap-3">
        @if ($tenant->status === 'active')
            <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" onsubmit="return confirm('Suspend {{ $tenant->name }}? Logins will be blocked.');">
                @csrf
                <button class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Suspend school</button>
            </form>
        @endif

        @if ($tenant->status === 'suspended')
            <form method="POST" action="{{ route('admin.tenants.resume', $tenant) }}">
                @csrf
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Resume school</button>
            </form>
        @endif

        @if ($tenant->status === 'failed')
            <form method="POST" action="{{ route('admin.tenants.retry', $tenant) }}">
                @csrf
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Retry provisioning</button>
            </form>
        @endif
    </div>
</div>
@endsection
```

- [ ] **Step 2: Add the controller methods**

Append these methods to `src/app/Http/Controllers/SuperAdmin/TenantController.php` (before the closing `}` of the class):

```php
    public function show(Tenant $tenant): View
    {
        $jobs = $tenant->load('provisioningJobs')->provisioningJobs()->orderByDesc('id')->get();

        return view('super-admin.tenants.show', ['tenant' => $tenant, 'jobs' => $jobs]);
    }

    public function suspend(Tenant $tenant): RedirectResponse
    {
        if ($tenant->status === 'active') {
            $tenant->update(['status' => 'suspended']);
        }

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('status', "{$tenant->name} suspended.");
    }

    public function resume(Tenant $tenant): RedirectResponse
    {
        if ($tenant->status === 'suspended') {
            $tenant->update(['status' => 'active']);
        }

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('status', "{$tenant->name} resumed.");
    }

    public function retry(Tenant $tenant): RedirectResponse
    {
        if ($tenant->status !== 'failed') {
            return redirect()->route('admin.tenants.show', $tenant);
        }

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('status', 'Retry not implemented for the capstone — investigate provisioning history above and re-create the school manually.');
    }
```

(`retry` is intentionally a no-op surface for the capstone. The provisioning history shows the actual error so the operator can debug; full retry from a partial state is fragile — out of scope.)

- [ ] **Step 3: Add the `provisioningJobs` relation to the Tenant model**

Open `src/app/Models/Tenant.php` and add this method (inside the class, after the existing methods):

```php
    public function provisioningJobs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\TenantProvisioningJob::class);
    }
```

- [ ] **Step 4: Verify**

```bash
docker exec tp-app php artisan route:clear
docker exec tp-app php artisan tinker --execute="
\$t = App\Models\Tenant::find(1);
echo 'tenant: ' . \$t->name . PHP_EOL;
echo 'jobs: ' . \$t->provisioningJobs()->count() . PHP_EOL;
"
```

Expected: `tenant: JCD` then `jobs: 0` (the JCD tenant was seeded directly, never went through the provisioning job).

- [ ] **Step 5: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Http/Controllers/SuperAdmin/TenantController.php src/app/Models/Tenant.php src/resources/views/super-admin/tenants/show.blade.php
git commit -m "feat(super-admin): tenant detail page + suspend/resume actions"
```

---

## Task 10: EnsureTenantIsActive middleware

**Files:**
- Create: `src/app/Http/Middleware/EnsureTenantIsActive.php`
- Create: `src/resources/views/tenancy/suspended.blade.php`
- Modify: `src/routes/tenant.php` (add the middleware to the existing tenancy group)
- Modify: `src/bootstrap/app.php` (register the middleware alias)

- [ ] **Step 1: Write the middleware**

`src/app/Http/Middleware/EnsureTenantIsActive.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if ($tenant && $tenant->status !== 'active') {
            return response()
                ->view('tenancy.suspended', ['tenant' => $tenant], 403);
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Write the suspended view**

`src/resources/views/tenancy/suspended.blade.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School suspended — {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow rounded-lg p-8 max-w-md">
        <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ $tenant->name }} is currently {{ $tenant->status }}.</h1>
        <p class="text-sm text-slate-600">Logins are temporarily blocked. Contact your platform administrator if this is unexpected.</p>
    </div>
</body>
</html>
```

- [ ] **Step 3: Register the middleware alias in `bootstrap/app.php`**

Inside the `withMiddleware` callback, in the `$middleware->alias([...])` array, add:

```php
'tenant.active' => \App\Http\Middleware\EnsureTenantIsActive::class,
```

- [ ] **Step 4: Apply the middleware to the tenant route group**

Open `src/routes/tenant.php`. Locate the existing tenancy middleware group:

```php
Route::middleware([
    'web',
    PreventAccessFromCentralDomains::class,
    InitializeTenancyBySubdomain::class,
])->group(function () {
```

Add `EnsureTenantIsActive` AFTER `InitializeTenancyBySubdomain` (it must run after `tenancy()` is initialized, since it reads the active tenant):

```php
use App\Http\Middleware\EnsureTenantIsActive;
// ... (top of file)

Route::middleware([
    'web',
    PreventAccessFromCentralDomains::class,
    InitializeTenancyBySubdomain::class,
    EnsureTenantIsActive::class,
])->group(function () {
```

- [ ] **Step 5: Verify by suspending JCD temporarily**

```bash
docker exec tp-app php artisan route:clear
docker exec tp-app php artisan tinker --execute="App\Models\Tenant::find(1)->update(['status' => 'suspended']);"
curl -sS -o /dev/null -w "HTTP %{http_code}\n" http://jcd.localhost:8081/login
docker exec tp-app php artisan tinker --execute="App\Models\Tenant::find(1)->update(['status' => 'active']);"
curl -sS -o /dev/null -w "HTTP %{http_code}\n" http://jcd.localhost:8081/login
```

Expected: first curl → `403`, second curl → `200`. (The intermediate tinker restores JCD to active so the rest of the demo keeps working.)

- [ ] **Step 6: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Http/Middleware/EnsureTenantIsActive.php src/resources/views/tenancy/suspended.blade.php src/routes/tenant.php src/bootstrap/app.php
git commit -m "feat(super-admin): EnsureTenantIsActive middleware blocks suspended schools"
```

---

## Task 11: Smoke test — full provisioning flow end-to-end

This task is manual + curl-based. Each step verifies a stage of the wizard.

- [ ] **Step 1: Restart and clear caches**

```bash
docker compose restart app
docker exec tp-app php artisan config:clear
docker exec tp-app php artisan route:clear
docker exec tp-app php artisan view:clear
```

- [ ] **Step 2: Browser — log in as super-admin**

Open `http://admin.localhost:8081/login` in a browser.
Email: `super@platform.test`
Password: `super123`
Expected: redirected to `/tenants` with the JCD school in the list.

- [ ] **Step 3: Browser — open the create form**

Click "New school". Expected: the wizard form renders with four fields (name, subdomain, admin name, admin email).

- [ ] **Step 4: Browser — submit a school**

Fill in:
- Name: `Demo University`
- Subdomain: `demo`
- Admin name: `Demo Admin`
- Admin email: `demo-admin@demo.test`

Click "Provision school". Expected: the success page appears within ~10s, showing:
- The URL `http://demo.localhost:8081`
- The admin email
- A randomly-generated 12-char temp password (note this — you'll need it).

- [ ] **Step 5: Verify the new tenant DB exists with the seeded primitives**

```bash
docker exec tp-db mysql -u root -psecret -e "SHOW DATABASES LIKE 'tenant_%';"
docker exec tp-db mysql -u root -psecret -e "USE tenant_2; SELECT COUNT(*) AS criteria FROM criteria; SELECT COUNT(*) AS questions FROM questions; SELECT COUNT(*) AS perms FROM role_permissions; SELECT name, email FROM users;"
```

Expected:
- `tenant_2` database exists
- `criteria`, `questions`, `role_permissions` all > 0 (seeded by template)
- One user row: `Demo Admin` / `demo-admin@demo.test`

- [ ] **Step 6: Browser — log in to the new school as the demo admin**

Open `http://demo.localhost:8081/login`.
Email: `demo-admin@demo.test`
Password: (the temp password from step 4)
Expected: forced to the change-password screen (because `must_change_password=true`). After changing, redirected to the dashboard.

- [ ] **Step 7: Browser — verify isolation between JCD and Demo**

Stay logged in to Demo. Confirm the dashboard is empty (no announcements, no faculty, no students). Then in a different browser tab, log in to `http://jcd.localhost:8081` with `admin@sample.com` / `admin123`. Confirm JCD's data is intact and unaffected.

- [ ] **Step 8: Suspend Demo from the super-admin dashboard**

Go to `http://admin.localhost:8081/tenants/2` → click "Suspend school". Then try `http://demo.localhost:8081/login` → expected: 403 with the "School suspended" page. Resume from the dashboard → confirm login works again.

- [ ] **Step 9: Capture the final state**

```bash
docker exec tp-db mysql -u root -psecret -e "USE central; SELECT id, name, subdomain, status, \`database\` FROM tenants;"
```

Expected: two rows — JCD (active, teachers_performance) and Demo University (active, tenant_2).

If anything fails, debug before proceeding to Task 12.

- [ ] **Step 10: No commit needed — this is a manual verification step**

---

## Task 12: Feature tests — super-admin auth + provisioning

**Files:**
- Create: `src/tests/Feature/SuperAdmin/AuthTest.php`
- Create: `src/tests/Feature/SuperAdmin/ProvisionTenantTest.php`

- [ ] **Step 1: Write `src/tests/Feature/SuperAdmin/AuthTest.php`**

```php
<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SuperAdmin;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function test_login_page_renders_on_admin_subdomain(): void
    {
        $response = $this->get('http://admin.localhost/login');

        $response->assertOk();
        $response->assertSee('Platform Console');
    }

    public function test_super_admin_can_authenticate_with_correct_credentials(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->post('http://admin.localhost/login', [
            'email'    => 'super@platform.test',
            'password' => 'super123',
        ]);

        $response->assertRedirect('http://admin.localhost/tenants');
        $this->assertAuthenticatedAs($admin, 'super_admin');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $response = $this->post('http://admin.localhost/login', [
            'email'    => 'super@platform.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('super_admin');
    }

    public function test_dashboard_redirects_unauthenticated_users_to_login(): void
    {
        $response = $this->get('http://admin.localhost/tenants');

        $response->assertRedirect('http://admin.localhost/login');
    }
}
```

- [ ] **Step 2: Write `src/tests/Feature/SuperAdmin/ProvisionTenantTest.php`**

```php
<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProvisionTenantTest extends TestCase
{
    /**
     * NOTE: This test creates a real tenant DB (tenant_<id>) and leaves it
     * in place for inspection. After running, drop it manually:
     *   docker exec tp-db mysql -u root -psecret -e "DROP DATABASE tenant_<id>"
     * and remove the tenant + domain row from central.
     */
    public function test_super_admin_can_provision_a_new_school(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Test School ' . uniqid(),
            'subdomain'   => 'test' . substr(md5(uniqid()), 0, 6),
            'admin_name'  => 'Test Admin',
            'admin_email' => 'admin@test.test',
        ]);

        $response->assertOk();
        $response->assertSee('is ready');
        $response->assertSee('admin@test.test');

        $newTenant = Tenant::orderByDesc('id')->first();
        $this->assertSame('active', $newTenant->status);
        $this->assertSame('tenant_' . $newTenant->id, $newTenant->getAttribute('database'));

        // Verify the tenant DB has the seeded template data
        $this->assertGreaterThan(0, DB::connection('mysql')->getPdo()->query("SELECT COUNT(*) FROM `{$newTenant->getAttribute('database')}`.criteria")->fetchColumn());
        $this->assertGreaterThan(0, DB::connection('mysql')->getPdo()->query("SELECT COUNT(*) FROM `{$newTenant->getAttribute('database')}`.users")->fetchColumn());
    }

    public function test_subdomain_validation_rejects_reserved_words(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Bad School',
            'subdomain'   => 'admin', // reserved
            'admin_name'  => 'X',
            'admin_email' => 'x@y.test',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_subdomain_validation_rejects_already_taken(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Duplicate School',
            'subdomain'   => 'jcd', // already exists
            'admin_name'  => 'X',
            'admin_email' => 'x@y.test',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }
}
```

- [ ] **Step 3: Run the tests**

```bash
docker exec tp-app php artisan test --filter=SuperAdmin 2>&1 | tail -25
```

Expected: 7 tests pass (4 auth + 3 provision). The `test_super_admin_can_provision_a_new_school` test takes longest (~5-15s) because it actually creates a DB and runs migrations.

If it fails, the most likely culprits:
- `tp_user` lacks privileges to CREATE DATABASE inside the test runner. Check the GRANT logic in `ProvisionTenantJob::createDatabase()`.
- Test environment uses SQLite for default DB. Tenant DBs require MySQL — verify `phpunit.xml` doesn't override the `mysql`/`central` connections.

- [ ] **Step 4: Cleanup left-over test tenants** (optional but recommended)

```bash
docker exec tp-db mysql -u root -psecret -e "USE central; SELECT id, subdomain, \`database\` FROM tenants WHERE subdomain LIKE 'test%';"
# For each test tenant id N: drop the DB and remove the rows.
```

This is manual cleanup; the test suite intentionally leaves artifacts so you can inspect them on failure.

- [ ] **Step 5: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/tests/Feature/SuperAdmin/
git commit -m "test(super-admin): auth + provisioning end-to-end coverage"
```

---

## Phase 2 Done — Verification Checklist

- [ ] `super_admin` guard + provider configured.
- [ ] `super@platform.test` / `super123` logs in at `admin.localhost:8081/login` and lands on `/tenants`.
- [ ] Tenants index lists JCD with status `active`.
- [ ] Wizard creates a Demo school in ~10s. Success page shows the temp password ONCE.
- [ ] `tenant_2` DB exists and has criteria, questions, role_permissions, and one Demo Admin user.
- [ ] Logging in as Demo Admin at `demo.localhost:8081/login` works; forces password change.
- [ ] Demo school's dashboard is empty (departments, faculty, students all empty).
- [ ] JCD's existing data is unchanged.
- [ ] Suspending Demo via the super-admin dashboard returns 403 on `demo.localhost:8081/login` until resumed.
- [ ] All Phase 1 + Phase 2 tests pass: `php artisan test 2>&1 | tail`.

When all boxes are ticked, Phase 3 (ML API tenant-awareness + cross-tenant leakage test) can begin.
