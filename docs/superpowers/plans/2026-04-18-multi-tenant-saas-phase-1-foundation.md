# Multi-Tenant SaaS — Phase 1: Foundation Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Install `stancl/tenancy` v3, create the central database schema, split routes into central/tenant, and register the existing `teachers_performance` database as tenant id 1 — without changing any existing app behavior.

**Architecture:** Layer stancl/tenancy on top of the existing Laravel app. A new `central` MySQL DB (in the same MySQL server) holds the tenant registry + super-admin accounts. All existing tenant-scoped migrations move into `database/migrations/tenant/` and run per-tenant via stancl. The existing `teachers_performance` DB is registered as tenant id 1 with zero data movement. All existing app routes move into `routes/tenant.php` and run only on tenant subdomains.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL 8, `stancl/tenancy` v3 (^3.9 — verify compatibility in Task 1), PHPUnit 12.

**Out of scope for Phase 1:** super-admin login UI, provisioning wizard, ML API changes, blank-school template seeder. Those land in Phase 2 and Phase 3.

---

## File Structure

**New files:**

| Path | Responsibility |
|---|---|
| `src/config/tenancy.php` | stancl configuration — tenant model, connection, central domains, migration path |
| `src/database/migrations/central/2026_04_18_000001_create_tenants_table.php` | central registry of schools |
| `src/database/migrations/central/2026_04_18_000002_create_super_admins_table.php` | platform operator accounts |
| `src/database/migrations/central/2026_04_18_000003_create_tenant_provisioning_jobs_table.php` | provisioning audit log |
| `src/app/Models/Tenant.php` | extends stancl Tenant base; declares `database` as a custom column |
| `src/app/Models/SuperAdmin.php` | Authenticatable for the super-admin guard |
| `src/app/Models/TenantProvisioningJob.php` | provisioning audit row |
| `src/database/seeders/CentralSeeder.php` | seeds first super-admin + tenant id 1 row pointing at existing DB |
| `src/routes/tenant.php` | every existing app route (moved from `web.php`) |
| `src/routes/admin.php` | placeholder file for Phase 2 (super-admin dashboard) |
| `src/tests/Feature/Tenancy/TenantSubdomainResolutionTest.php` | verifies subdomain → tenant DB swap |

**Modified files:**

| Path | Change |
|---|---|
| `src/composer.json` | add `stancl/tenancy: ^3.9` |
| `src/config/database.php` | add `central` connection block |
| `src/.env` | add `DB_CENTRAL_DATABASE`, set `SESSION_DOMAIN`, register `CENTRAL_DOMAINS` (consumed by tenancy.php) |
| `src/bootstrap/app.php` | register tenant + admin route files via `then` callback; add tenancy middleware aliases |
| `src/routes/web.php` | slim down to one central landing route (redirect to `admin.localhost` or a "select your school" stub) |

**Files moved (no content change):**

Move every file matching `src/database/migrations/2026_*` into `src/database/migrations/tenant/`. Includes departments, users, faculty/student profiles, criteria, questions, evaluations, announcements, AI metrics — every domain table. The two L11-base migrations (`0001_01_01_000001_create_cache_table.php`, `0001_01_01_000002_create_jobs_table.php`) and the early `0001_01_*` files also move, since they exist in `teachers_performance` today and any future tenant needs them too.

**Files explicitly NOT touched in Phase 1:**

- `ml_api/app.py` — Phase 3.
- `app/Http/Controllers/**` — no controller changes; everything keeps working under the swap.
- `app/Models/User.php` — stays a tenant-scoped model.
- `docker/nginx/default.conf` — already uses `server_name _` (catch-all), so wildcard subdomain works without nginx changes.
- `docker-compose.yml` — central DB lives in the existing `tp-db` MySQL container, no new service.

---

## Task 1: Install stancl/tenancy and verify Laravel 13 compatibility

**Files:**
- Modify: `src/composer.json`
- Create: `src/config/tenancy.php` (via `vendor:publish`)

- [ ] **Step 1: Add the package**

Run from the host (so composer.json + composer.lock update on disk):

```bash
docker exec tp-app composer require stancl/tenancy:^3.9
```

Expected: composer pulls the package and updates composer.lock. If composer reports a Laravel 13 conflict, try `^3.8` and `^3.10` in turn. If all fail, stop and report — the rest of the plan assumes the package installs cleanly.

- [ ] **Step 2: Run the install command (publishes config + service provider)**

```bash
docker exec tp-app php artisan tenancy:install
```

Expected: prints "Tenancy installed successfully" and creates `src/config/tenancy.php` plus a stub `src/database/migrations/2019_09_15_000010_create_tenants_table.php` and `..._create_domains_table.php`.

- [ ] **Step 3: Delete the stub migrations published by stancl**

We are writing our own central migrations with a custom schema (we don't use stancl's `domains` table — we use a `subdomain` column on `tenants` instead).

```bash
docker exec tp-app rm database/migrations/2019_09_15_000010_create_tenants_table.php
docker exec tp-app rm database/migrations/2019_09_15_000020_create_domains_table.php
```

Expected: both files removed. Run `ls src/database/migrations/2019_*` from host — should return no matches.

- [ ] **Step 4: Verify the existing app still boots**

```bash
docker exec tp-app php artisan route:list --columns=uri,name | head -20
```

Expected: route list prints without errors. The existing routes are still there.

- [ ] **Step 5: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/composer.json src/composer.lock src/config/tenancy.php
git commit -m "chore(tenancy): install stancl/tenancy v3 package"
```

---

## Task 2: Add `central` database connection

**Files:**
- Modify: `src/config/database.php` (insert new connection block)
- Modify: `src/.env` (add central DB env vars)

- [ ] **Step 1: Add `central` connection to `config/database.php`**

Open `src/config/database.php`. Inside the `'connections' => [ ... ]` array, immediately after the `'mysql'` block (around line 65), insert:

```php
        'central' => [
            'driver'    => 'mysql',
            'url'       => env('DB_CENTRAL_URL'),
            'host'      => env('DB_CENTRAL_HOST', env('DB_HOST', '127.0.0.1')),
            'port'      => env('DB_CENTRAL_PORT', env('DB_PORT', '3306')),
            'database'  => env('DB_CENTRAL_DATABASE', 'central'),
            'username'  => env('DB_CENTRAL_USERNAME', env('DB_USERNAME', 'root')),
            'password'  => env('DB_CENTRAL_PASSWORD', env('DB_PASSWORD', '')),
            'charset'   => env('DB_CHARSET', 'utf8mb4'),
            'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ],
```

Falls back to the same MySQL server credentials as the tenant connection.

- [ ] **Step 2: Add env vars to `src/.env`**

Append after the existing DB block:

```
# Central (multi-tenancy control plane) database
DB_CENTRAL_DATABASE=central
```

- [ ] **Step 3: Create the central database in MySQL**

```bash
docker exec tp-db mysql -u root -psecret -e "CREATE DATABASE IF NOT EXISTS central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Expected: command exits 0, no output. Verify with:

```bash
docker exec tp-db mysql -u root -psecret -e "SHOW DATABASES LIKE 'central';"
```

Expected: lists `central`.

- [ ] **Step 4: Verify Laravel can connect to the central DB**

```bash
docker exec tp-app php artisan tinker --execute="echo DB::connection('central')->getDatabaseName();"
```

Expected: prints `central`.

- [ ] **Step 5: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/config/database.php src/.env
git commit -m "feat(tenancy): add central database connection"
```

---

## Task 3: Create central migrations directory and `tenants` table

**Files:**
- Create: `src/database/migrations/central/2026_04_18_000001_create_tenants_table.php`

- [ ] **Step 1: Create the directory**

```bash
docker exec tp-app mkdir -p database/migrations/central
```

- [ ] **Step 2: Write the migration**

Create `src/database/migrations/central/2026_04_18_000001_create_tenants_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('subdomain')->unique();
            $table->string('database');
            $table->enum('status', ['provisioning', 'active', 'suspended', 'failed'])
                ->default('provisioning');
            $table->json('data')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenants');
    }
};
```

- [ ] **Step 3: Run the migration**

```bash
docker exec tp-app php artisan migrate --path=database/migrations/central --database=central
```

Expected output includes `Migrating: 2026_04_18_000001_create_tenants_table` followed by `Migrated`.

- [ ] **Step 4: Verify the table exists**

```bash
docker exec tp-db mysql -u root -psecret -e "USE central; DESCRIBE tenants;"
```

Expected: prints columns `id`, `name`, `subdomain`, `database`, `status`, `data`, `created_at`, `updated_at`.

- [ ] **Step 5: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/database/migrations/central/2026_04_18_000001_create_tenants_table.php
git commit -m "feat(tenancy): central migration — tenants table"
```

---

## Task 4: Create `super_admins` central migration

**Files:**
- Create: `src/database/migrations/central/2026_04_18_000002_create_super_admins_table.php`

- [ ] **Step 1: Write the migration**

Create the file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('super_admins', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('super_admins');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
docker exec tp-app php artisan migrate --path=database/migrations/central --database=central
```

Expected: `Migrating: 2026_04_18_000002_create_super_admins_table` → `Migrated`.

- [ ] **Step 3: Verify**

```bash
docker exec tp-db mysql -u root -psecret -e "USE central; DESCRIBE super_admins;"
```

Expected: columns `id`, `name`, `email`, `password`, `is_active`, `remember_token`, `created_at`, `updated_at`.

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/database/migrations/central/2026_04_18_000002_create_super_admins_table.php
git commit -m "feat(tenancy): central migration — super_admins table"
```

---

## Task 5: Create `tenant_provisioning_jobs` central migration

**Files:**
- Create: `src/database/migrations/central/2026_04_18_000003_create_tenant_provisioning_jobs_table.php`

- [ ] **Step 1: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->create('tenant_provisioning_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->enum('status', ['pending', 'running', 'succeeded', 'failed'])
                ->default('pending');
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('tenant_provisioning_jobs');
    }
};
```

- [ ] **Step 2: Run the migration**

```bash
docker exec tp-app php artisan migrate --path=database/migrations/central --database=central
```

Expected: `Migrating: 2026_04_18_000003_create_tenant_provisioning_jobs_table` → `Migrated`.

- [ ] **Step 3: Verify**

```bash
docker exec tp-db mysql -u root -psecret -e "USE central; SHOW TABLES;"
```

Expected: lists `tenants`, `super_admins`, `tenant_provisioning_jobs`, `migrations`.

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/database/migrations/central/2026_04_18_000003_create_tenant_provisioning_jobs_table.php
git commit -m "feat(tenancy): central migration — tenant_provisioning_jobs table"
```

---

## Task 6: Create the `Tenant` model (extends stancl base)

**Files:**
- Create: `src/app/Models/Tenant.php`

- [ ] **Step 1: Write the model**

```php
<?php

namespace App\Models;

use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase;

    protected $connection = 'central';
    protected $table = 'tenants';

    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * Columns NOT stored in the `data` JSON bag.
     * Everything else gets transparently moved into `data` by stancl.
     */
    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'subdomain',
            'database',
            'status',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * stancl asks each tenant for its DB name when switching connections.
     * We override the default (which uses the `tenancy_db_name` data key)
     * to read from our explicit `database` column.
     */
    public function getDatabaseName(): string
    {
        return $this->getAttribute('database');
    }
}
```

- [ ] **Step 2: Verify the model loads**

```bash
docker exec tp-app php artisan tinker --execute="echo App\Models\Tenant::class;"
```

Expected: prints `App\Models\Tenant`.

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Models/Tenant.php
git commit -m "feat(tenancy): Tenant model with custom database column"
```

---

## Task 7: Create the `SuperAdmin` model

**Files:**
- Create: `src/app/Models/SuperAdmin.php`

- [ ] **Step 1: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SuperAdmin extends Authenticatable
{
    use Notifiable;

    protected $connection = 'central';
    protected $table = 'super_admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'  => 'hashed',
            'is_active' => 'boolean',
        ];
    }
}
```

- [ ] **Step 2: Verify**

```bash
docker exec tp-app php artisan tinker --execute="echo App\Models\SuperAdmin::class;"
```

Expected: prints `App\Models\SuperAdmin`.

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Models/SuperAdmin.php
git commit -m "feat(tenancy): SuperAdmin model on central connection"
```

---

## Task 8: Create the `TenantProvisioningJob` model

**Files:**
- Create: `src/app/Models/TenantProvisioningJob.php`

- [ ] **Step 1: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantProvisioningJob extends Model
{
    protected $connection = 'central';
    protected $table = 'tenant_provisioning_jobs';

    protected $fillable = [
        'tenant_id',
        'status',
        'error',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at'  => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

- [ ] **Step 2: Verify**

```bash
docker exec tp-app php artisan tinker --execute="echo App\Models\TenantProvisioningJob::class;"
```

Expected: prints `App\Models\TenantProvisioningJob`.

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Models/TenantProvisioningJob.php
git commit -m "feat(tenancy): TenantProvisioningJob audit model"
```

---

## Task 9: Move existing migrations into `database/migrations/tenant/`

These migrations describe the per-school schema. They already ran on `teachers_performance` so this move is purely a directory reorganization — no DB change.

**Files:**
- Move: every file in `src/database/migrations/*.php` (except the central ones from Tasks 3–5) into `src/database/migrations/tenant/`

- [ ] **Step 1: Create the tenant migrations directory**

```bash
docker exec tp-app mkdir -p database/migrations/tenant
```

- [ ] **Step 2: Move all top-level migrations into `tenant/`**

From the host (PowerShell or bash, doesn't matter — we want the move to be tracked by git):

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git mv src/database/migrations/0001_01_00_000000_create_departments_table.php src/database/migrations/tenant/
git mv src/database/migrations/0001_01_01_000000_create_users_table.php src/database/migrations/tenant/
git mv src/database/migrations/0001_01_01_000001_create_cache_table.php src/database/migrations/tenant/
git mv src/database/migrations/0001_01_01_000002_create_jobs_table.php src/database/migrations/tenant/
```

Then for the dated 2026 migrations, list and move them all:

```bash
ls src/database/migrations/2026_*.php | xargs -I{} git mv {} src/database/migrations/tenant/
```

(If on Windows without `xargs`, use a PowerShell loop:
`Get-ChildItem src/database/migrations/2026_*.php | ForEach-Object { git mv $_.FullName "src/database/migrations/tenant/$($_.Name)" }`)

- [ ] **Step 3: Verify the move**

```bash
ls src/database/migrations/tenant/ | wc -l
ls src/database/migrations/*.php 2>/dev/null | wc -l
```

Expected: 50+ files in `tenant/`, 0 loose `.php` files in `migrations/` (only the `central/` and `tenant/` subdirectories remain).

- [ ] **Step 4: Verify `php artisan migrate` is now a no-op against the existing tenant DB**

```bash
docker exec tp-app php artisan migrate --pretend --database=mysql
```

Expected: no migrations listed (Laravel does not recurse into subdirectories by default, so the moved files are no longer auto-discovered — exactly what we want).

If migrations ARE listed, the reorg didn't take effect and the existing `teachers_performance` DB would be re-targeted incorrectly. Stop and investigate.

- [ ] **Step 5: Verify the existing tenant DB still has all its tables**

```bash
docker exec tp-db mysql -u root -psecret -e "USE teachers_performance; SHOW TABLES;" | wc -l
```

Expected: same count as before the move (40+ tables). The move was filesystem-only.

- [ ] **Step 6: Commit the move**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git commit -m "refactor(tenancy): relocate domain migrations to tenant/ subdir

Files unchanged — pure directory move so stancl can target them as the
tenant migration path. Existing teachers_performance DB unaffected."
```

---

## Task 10: Configure `tenancy.php`

**Files:**
- Modify: `src/config/tenancy.php` (replace the auto-published file with our configuration)

- [ ] **Step 1: Replace the file contents**

Open `src/config/tenancy.php` and replace its entire contents with:

```php
<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as DefaultTenant;

return [
    'tenant_model'          => \App\Models\Tenant::class,
    'id_generator'          => null, // we use auto-incrementing bigint ids

    'domain_model'          => Domain::class, // unused — we resolve by subdomain column

    /*
     * Hostnames hit on the central domain are NEVER treated as tenants.
     * Add 'localhost' (Docker dev), 'admin.localhost' (super-admin UI),
     * plus your production domain.
     */
    'central_domains' => array_filter(
        array_map('trim', explode(',', env('TENANCY_CENTRAL_DOMAINS', 'localhost,admin.localhost')))
    ),

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => 'central',
        'template_tenant_connection' => null,
        'prefix'   => 'tenant_',
        'suffix'   => '',
        'managers' => [
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks'       => ['local', 'public'],
        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],

    'queue' => [
        // tenancy bootstrap re-runs on each queued job
    ],

    'features' => [
        // we register custom features only when needed
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path'  => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        // populated in Phase 2 (TenantTemplateSeeder)
    ],
];
```

- [ ] **Step 2: Add the central-domains env var to `.env`**

Append to `src/.env`:

```
TENANCY_CENTRAL_DOMAINS=localhost,admin.localhost
```

- [ ] **Step 3: Verify config is parseable**

```bash
docker exec tp-app php artisan config:clear
docker exec tp-app php artisan tinker --execute="echo implode(',', config('tenancy.central_domains'));"
```

Expected: prints `localhost,admin.localhost`.

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/config/tenancy.php src/.env
git commit -m "feat(tenancy): configure tenancy.php — central domains, tenant migration path"
```

---

## Task 11: Move existing routes into `routes/tenant.php` and slim `routes/web.php`

`routes/web.php` becomes the central-domain routes (one redirect). `routes/tenant.php` gets every existing app route.

**Files:**
- Create: `src/routes/tenant.php`
- Modify: `src/routes/web.php`

- [ ] **Step 1: Copy current `web.php` contents into a new `tenant.php`**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
cp src/routes/web.php src/routes/tenant.php
```

- [ ] **Step 2: Replace `src/routes/web.php` with the central-only stub**

Overwrite the entire file:

```php
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
```

- [ ] **Step 3: Create the central landing view**

Create `src/resources/views/central/landing.blade.php`:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teachers Performance Platform</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 480px; margin: 80px auto; padding: 0 24px; color: #1f2937; }
        h1 { font-size: 1.5rem; margin-bottom: 8px; }
        p { color: #4b5563; line-height: 1.5; }
        code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Teachers Performance — Multi-Tenant Platform</h1>
    <p>This is the central platform domain. To access a school, use its subdomain — for example <code>jcd.localhost:8081</code>.</p>
    <p>Platform operators: visit <code>admin.localhost:8081</code> (Phase 2).</p>
</body>
</html>
```

- [ ] **Step 4: Add tenancy middleware at the top of `routes/tenant.php`**

Open `src/routes/tenant.php`. At the very top of the file (after the `<?php` tag and `use` statements, before `Route::get('/', ...)`), wrap the existing route declarations in a middleware group. The simplest mechanical change: leave all the existing routes as they are, and ADD a single wrapper above them.

Replace:

```php
use Illuminate\Support\Facades\Route;

// Root: redirect unauthenticated visitors to the login page
Route::get('/', fn() => redirect()->route('login'));
```

With:

```php
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyBySubdomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyBySubdomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Root: redirect unauthenticated visitors to the login page
    Route::get('/', fn() => redirect()->route('login'));
```

Then at the very end of the file (after the last `Route::...` line, before any closing PHP tag), add a single closing `});` to close the middleware group.

- [ ] **Step 5: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/routes/web.php src/routes/tenant.php src/resources/views/central/landing.blade.php
git commit -m "feat(tenancy): split routes — central web.php + tenant.php

routes/web.php now serves only the central landing page on the platform
domain. routes/tenant.php holds every existing app route, gated by stancl
subdomain tenancy middleware."
```

---

## Task 12: Create `routes/admin.php` placeholder

Phase 2 fills this in. Phase 1 just creates the file so `bootstrap/app.php` can register it without erroring.

**Files:**
- Create: `src/routes/admin.php`

- [ ] **Step 1: Write the placeholder**

```php
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
```

- [ ] **Step 2: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/routes/admin.php
git commit -m "feat(tenancy): admin route file placeholder for Phase 2"
```

---

## Task 13: Register the new route files in `bootstrap/app.php`

**Files:**
- Modify: `src/bootstrap/app.php`

- [ ] **Step 1: Replace the file contents**

```php
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
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

- [ ] **Step 2: Add `APP_ADMIN_DOMAIN` to `.env`**

Append to `src/.env`:

```
APP_ADMIN_DOMAIN=admin.localhost
```

- [ ] **Step 3: Verify route list now shows tenant + admin routes**

```bash
docker exec tp-app php artisan config:clear && docker exec tp-app php artisan route:clear
docker exec tp-app php artisan route:list --columns=domain,method,uri,name | head -30
```

Expected:
- The `central.landing` route appears with no domain restriction.
- The `admin.landing` route appears with `domain=admin.localhost`.
- Existing tenant routes (login, dashboard, etc.) appear without explicit domains (they use middleware-based subdomain resolution).

If you see "InitializeTenancyBySubdomain not found", check Task 1 step 1 (package install).

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/bootstrap/app.php src/.env
git commit -m "feat(tenancy): register tenant + admin route files"
```

---

## Task 14: Seed the central DB — first super-admin + JCD as tenant 1

This is the one-time migration of School #1: insert a `tenants` row pointing at the existing `teachers_performance` database. No data movement.

**Files:**
- Create: `src/database/seeders/CentralSeeder.php`

- [ ] **Step 1: Write the seeder**

```php
<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentralSeeder extends Seeder
{
    public function run(): void
    {
        // First super-admin (you, the platform operator).
        SuperAdmin::firstOrCreate(
            ['email' => 'super@platform.test'],
            [
                'name'      => 'Platform Super Admin',
                'password'  => Hash::make('super123'),
                'is_active' => true,
            ],
        );

        // Existing JCD school registered as tenant id 1.
        // Points at the existing teachers_performance DB — no data movement.
        Tenant::firstOrCreate(
            ['subdomain' => 'jcd'],
            [
                'id'       => 1,
                'name'     => 'JCD',
                'database' => 'teachers_performance',
                'status'   => 'active',
            ],
        );
    }
}
```

- [ ] **Step 2: Run the seeder against the central DB**

```bash
docker exec tp-app php artisan db:seed --class=CentralSeeder --database=central
```

Expected output: `Database seeding completed successfully.`

- [ ] **Step 3: Verify the rows exist**

```bash
docker exec tp-db mysql -u root -psecret -e "USE central; SELECT id, subdomain, database, status FROM tenants; SELECT id, email FROM super_admins;"
```

Expected: one tenant row (`id=1, subdomain=jcd, database=teachers_performance, status=active`) and one super_admin row.

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/database/seeders/CentralSeeder.php
git commit -m "feat(tenancy): CentralSeeder — first super-admin + JCD tenant row"
```

---

## Task 15: Smoke test — existing app still works at `jcd.localhost:8081`

Manual verification step. No code, no commit — just confirming the foundation works end-to-end.

- [ ] **Step 1: Restart the app container so all config/route changes are loaded**

```bash
docker compose restart app nginx
docker exec tp-app php artisan config:clear
docker exec tp-app php artisan route:clear
docker exec tp-app php artisan view:clear
```

- [ ] **Step 2: Verify the central landing page**

Open in browser: `http://localhost:8081/`
Expected: the "Teachers Performance — Multi-Tenant Platform" landing page from Task 11.

- [ ] **Step 3: Verify the JCD tenant resolves**

Open in browser: `http://jcd.localhost:8081/`
Expected: redirected to `/login` (the existing login page renders).

- [ ] **Step 4: Verify login still works**

Log in at `http://jcd.localhost:8081/login` with:
- email: `admin@sample.com`
- password: `admin123`

Expected: redirected to the existing dashboard. All existing data (users, departments, criteria, announcements) is visible.

- [ ] **Step 5: Verify a few existing pages render without error**

Click through:
- Dashboard
- Announcements (should show the work from `feature/announcements-module`)
- One of the evaluation pages
- Settings

Expected: every page renders. No "no such table" or "Access denied" SQL errors.

- [ ] **Step 6: Verify tenant routes are blocked on the central domain**

Open in browser: `http://localhost:8081/login`
Expected: 404 OR a "central domain" rejection message (depends on how stancl's `PreventAccessFromCentralDomains` responds — both are correct behaviour). The central domain has no `/login` route.

- [ ] **Step 7: Verify the admin domain placeholder**

Open in browser: `http://admin.localhost:8081/`
Expected: the plaintext "Super-admin dashboard — coming in Phase 2." response from Task 12.

- [ ] **Step 8: If anything fails, do not proceed to Phase 2**

Common issues + fixes:

| Symptom | Likely cause | Fix |
|---|---|---|
| `jcd.localhost` shows "site cannot be reached" | Browser doesn't auto-resolve `*.localhost` | Add `127.0.0.1 jcd.localhost admin.localhost` to `/etc/hosts` (or `C:\Windows\System32\drivers\etc\hosts`) |
| All pages 500 with "no such table tenants" | tenancy middleware reading wrong DB | Verify `config/tenancy.php` `database.central_connection` is `'central'` and Task 14 actually wrote the row |
| Login fails with "user not found" | the connection didn't swap; you're querying the central DB instead of `teachers_performance` | Verify the route is inside the `InitializeTenancyBySubdomain` middleware group from Task 11 step 4 |
| Existing pages 500 with `DB::connection('mysql')` errors | hardcoded connection refs (Phase 1 risk #1) | Grep for them: `grep -rn "DB::connection('mysql')" src/app src/routes`. Plan to address in Phase 3 verification — for Phase 1, only fix if they break the smoke test. |

---

## Task 16: Write the tenant-resolution feature test

**Files:**
- Create: `src/tests/Feature/Tenancy/TenantSubdomainResolutionTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use Tests\TestCase;

class TenantSubdomainResolutionTest extends TestCase
{
    /** @test */
    public function jcd_subdomain_resolves_to_the_existing_teachers_performance_database(): void
    {
        $tenant = Tenant::where('subdomain', 'jcd')->firstOrFail();

        $this->assertSame('teachers_performance', $tenant->getDatabaseName());
        $this->assertSame('active', $tenant->status);
    }

    /** @test */
    public function central_landing_page_is_accessible_on_central_domain(): void
    {
        $response = $this->get('http://localhost/');

        $response->assertOk();
        $response->assertSee('Multi-Tenant Platform');
    }

    /** @test */
    public function admin_placeholder_is_accessible_on_admin_subdomain(): void
    {
        $response = $this->get('http://admin.localhost/');

        $response->assertOk();
        $response->assertSee('Super-admin dashboard');
    }
}
```

- [ ] **Step 2: Run the test**

```bash
docker exec tp-app php artisan test --filter=TenantSubdomainResolutionTest
```

Expected: **all three pass**. The test reads the tenants table seeded in Task 14 and verifies the central + admin routes respond.

If the first test fails with "No query results for model [App\Models\Tenant]", the seeder from Task 14 didn't run against the central DB the test is using. Verify the test environment's `DB_CENTRAL_DATABASE` and that the seeder ran.

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/tests/Feature/Tenancy/TenantSubdomainResolutionTest.php
git commit -m "test(tenancy): subdomain resolution + central/admin landing"
```

---

## Phase 1 Done — Verification Checklist

- [ ] `central` MySQL database exists with three tables (`tenants`, `super_admins`, `tenant_provisioning_jobs`).
- [ ] One row in `tenants` (id=1, subdomain=jcd, database=teachers_performance, status=active).
- [ ] One row in `super_admins`.
- [ ] All previously-flat migrations now live in `database/migrations/tenant/`.
- [ ] `routes/web.php` is the central landing page only.
- [ ] `routes/tenant.php` contains all existing app routes wrapped in tenancy middleware.
- [ ] `routes/admin.php` exists as a placeholder.
- [ ] `bootstrap/app.php` registers all three route files with correct domain constraints.
- [ ] `http://localhost:8081/` shows the central landing page.
- [ ] `http://jcd.localhost:8081/login` shows the existing login page; logging in lands on the existing dashboard with all existing data.
- [ ] `http://admin.localhost:8081/` shows the plaintext placeholder.
- [ ] `php artisan test --filter=TenantSubdomainResolutionTest` passes.

When all boxes are ticked, Phase 2 (super-admin dashboard + provisioning) can begin.
