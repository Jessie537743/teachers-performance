# Activation Codes + Subscription Plans Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the temp-password handoff with a 12-char activation code that the school admin redeems at `localhost:8081/activate` to set their own password and lock in a subscription plan tier (Free / Pro / Enterprise). Add a public pricing landing on the central domain and an internal Plans dashboard inside the super-admin console.

**Architecture:** Two new central tables (`tenants.plan` column + `activation_codes` table). One config file for the three plan tiers. The wizard (`admin.localhost/tenants/create`) generates a code and shows it once instead of creating the user. The activation page (`localhost:8081/activate`) accepts the code + a chosen password, creates the user inside the tenant DB, and transitions the tenant from `pending_activation` to `active`. Plans are marketing-only — no feature gating.

**Tech Stack:** Laravel 13, PHP 8.3+, MySQL 8, `stancl/tenancy` v3.10, Tailwind CSS (CDN for landing, existing build for super-admin), PHPUnit 12.

**Out of scope (explicitly deferred):**
- Real billing / payments — plans are labels only.
- Plan-based feature gating — every school gets every feature.
- Email delivery of activation codes — super-admin copies the code on screen.
- Public self-signup — schools still created by super-admin.
- Plan upgrade/downgrade UI — manual DB edit if needed.

---

## File Structure

**New files:**

| Path | Responsibility |
|---|---|
| `src/config/plans.php` | Three fixed plan tier definitions (slug, name, price, features, highlight) |
| `src/database/migrations/central/2026_04_19_000001_add_plan_to_tenants_table.php` | Add `plan` string column to `tenants`, default `free` |
| `src/database/migrations/central/2026_04_19_000002_extend_tenants_status_enum.php` | Add `pending_activation` to the `tenants.status` enum |
| `src/database/migrations/central/2026_04_19_000003_create_activation_codes_table.php` | Central registry of issued codes |
| `src/app/Models/ActivationCode.php` | Eloquent model on central connection; `generate()` + `isRedeemable()` helpers |
| `src/app/Http/Controllers/Central/ActivationController.php` | Public GET/POST `/activate` |
| `src/app/Http/Controllers/SuperAdmin/PlanController.php` | GET `/plans` (super-admin internal) |
| `src/resources/views/central/activate/show.blade.php` | Activation form |
| `src/resources/views/central/activate/invalid.blade.php` | "Invalid / expired / revoked / used" error page |
| `src/resources/views/central/activate/success.blade.php` | "School is ready" + link to subdomain login |
| `src/resources/views/super-admin/plans/index.blade.php` | Internal plans dashboard |
| `src/resources/views/super-admin/tenants/created.blade.php` | NEW success page after wizard — shows the activation code (replaces the temp-password reveal) |
| `src/tests/Feature/Activation/WizardCreatesActivationCodeTest.php` | Wizard creates tenant in `pending_activation` + unredeemed code; no user yet |
| `src/tests/Feature/Activation/ActivationFlowTest.php` | Full GET + POST `/activate` redemption coverage + invalid-state cases |
| `src/tests/Feature/Activation/RegenerateCodeTest.php` | Revoke + regenerate cycle |

**Modified files:**

| Path | Change |
|---|---|
| `src/resources/views/central/landing.blade.php` | Replace minimal landing with hero + 3-tier pricing + how-it-works + footer |
| `src/routes/web.php` | Register `/activate` GET/POST per central domain (skip admin domain), like the landing route |
| `src/routes/admin.php` | Add `/plans` index + `/tenants/{tenant}/codes/regenerate` + `/tenants/{tenant}/codes/{code}/revoke` |
| `src/app/Http/Controllers/SuperAdmin/TenantController.php` | `store()` no longer creates user/temp-password — generates `ActivationCode` and renders `created.blade.php`. Add `regenerateCode()` + `revokeCode()`. Add `?plan=` and `?status=` filters on `index()`. Add activation section to `show()`. |
| `src/app/Models/Tenant.php` | Add `activationCodes()` HasMany; helper `currentUnredeemedCode()` |
| `src/app/Jobs/ProvisionTenantJob.php` | Drop `createFirstAdmin()` step. Constructor takes only the `Tenant`. Caller decides post-success status. |
| `src/app/Http/Middleware/EnsureTenantIsActive.php` | Allow non-active statuses through with the suspended view; pass status in to view |
| `src/resources/views/tenancy/suspended.blade.php` | Switch copy on `$tenant->status` (suspended / pending_activation / failed / provisioning) |
| `src/resources/views/super-admin/layout.blade.php` | Add "Plans" nav link |
| `src/resources/views/super-admin/tenants/index.blade.php` | Add `Plan` column with color-coded badge; surface `?plan=` and `?status=pending_activation` filters |
| `src/resources/views/super-admin/tenants/show.blade.php` | Add Activation section above Provisioning History |

**Files explicitly NOT touched:**
- ML API (`ml_api/app.py`) — Phase 3 work; plans don't gate ML features.
- Existing tenant-side controllers (User model, evaluations, announcements) — unchanged.
- Existing seeders.

---

## Pre-Plan Notes (read before Task 1)

**Status enum migration in Laravel 13:** the `change()` method on enum columns works natively in Laravel 11+ (no `doctrine/dbal` needed). If you see a "doctrine/dbal not installed" error, fall back to dropping + recreating the column with the wider enum and a backfill statement. The plan assumes the native path works.

**Multiple central domains:** `routes/web.php` already loops over `config('tenancy.central_domains')` to register the landing per central domain (skipping the admin subdomain). The activation route uses the same loop pattern.

**The wizard's existing temp-password reveal goes away.** No partial coexistence — the new code-based flow replaces it. Existing JCD school is grandfathered (already `active`); we don't issue a code for it.

**Throttling:** Laravel's built-in `throttle:5,1` middleware applies to the POST `/activate` route to deter brute force. Codes are 12 chars over a 32-char alphabet (~3×10²¹ space) but throttle is cheap.

---

## Task 1: Plans config + tenants.plan column + status enum extension

**Files:**
- Create: `src/config/plans.php`
- Create: `src/database/migrations/central/2026_04_19_000001_add_plan_to_tenants_table.php`
- Create: `src/database/migrations/central/2026_04_19_000002_extend_tenants_status_enum.php`

- [ ] **Step 1: Write `src/config/plans.php`**

```php
<?php

return [
    'free' => [
        'slug'      => 'free',
        'name'      => 'Free',
        'price'     => 0,
        'period'    => 'forever',
        'tagline'   => 'Try the platform with limited evaluations.',
        'features'  => [
            'Up to 50 students',
            'Manual evaluations only',
            'Basic announcements',
            'Email support',
        ],
        'highlight' => false,
    ],
    'pro' => [
        'slug'      => 'pro',
        'name'      => 'Pro',
        'price'     => 99,
        'period'    => 'per month',
        'tagline'   => 'Full evaluation toolkit for growing schools.',
        'features'  => [
            'Unlimited students',
            'AI-powered performance predictions',
            'Sentiment analysis on feedback',
            'All evaluation types (peer, dean, self)',
            'Priority email support',
        ],
        'highlight' => true,
    ],
    'enterprise' => [
        'slug'      => 'enterprise',
        'name'      => 'Enterprise',
        'price'     => 'Custom',
        'period'   => '',
        'tagline'  => 'For multi-campus institutions.',
        'features' => [
            'Everything in Pro',
            'Custom branding',
            'Dedicated success manager',
            'On-premise deployment option',
            'SLA-backed uptime',
        ],
        'highlight' => false,
    ],
];
```

- [ ] **Step 2: Write the `add_plan_to_tenants_table` migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->string('plan', 32)->default('free')->after('status');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->dropColumn('plan');
        });
    }
};
```

- [ ] **Step 3: Write the `extend_tenants_status_enum` migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add `pending_activation` to the existing enum. Laravel 13 supports
        // change() on enum columns natively (no doctrine/dbal required).
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->enum('status', ['provisioning', 'pending_activation', 'active', 'suspended', 'failed'])
                ->default('provisioning')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('tenants', function (Blueprint $table) {
            $table->enum('status', ['provisioning', 'active', 'suspended', 'failed'])
                ->default('provisioning')
                ->change();
        });
    }
};
```

- [ ] **Step 4: Run the migrations**

```bash
docker exec tp-app php artisan migrate --path=database/migrations/central --database=central
```

Expected: both migrations marked DONE.

- [ ] **Step 5: Verify**

```bash
docker exec tp-app php artisan tinker --execute="
echo 'plans: ' . implode(',', array_keys(config('plans'))) . PHP_EOL;
echo 'JCD plan: ' . App\Models\Tenant::find(1)->getAttribute('plan') . PHP_EOL;
"
docker exec tp-db mysql -u root -psecret -e "USE central; SHOW COLUMNS FROM tenants WHERE Field IN ('status', 'plan');" 2>&1 | grep -v Warning
```

Expected: `plans: free,pro,enterprise`. `JCD plan: free`. The `status` enum column shows the 5 values.

- [ ] **Step 6: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/config/plans.php src/database/migrations/central/2026_04_19_000001_add_plan_to_tenants_table.php src/database/migrations/central/2026_04_19_000002_extend_tenants_status_enum.php
git commit -m "feat(plans): add plans config, tenants.plan column, pending_activation status"
```

---

## Task 2: `activation_codes` table + `ActivationCode` model

**Files:**
- Create: `src/database/migrations/central/2026_04_19_000003_create_activation_codes_table.php`
- Create: `src/app/Models/ActivationCode.php`

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
        Schema::connection('central')->create('activation_codes', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id');
            $table->string('code', 20)->unique();
            $table->string('plan', 32);
            $table->string('intended_admin_name');
            $table->string('intended_admin_email');
            $table->enum('status', ['unredeemed', 'redeemed', 'revoked', 'expired'])
                ->default('unredeemed');
            $table->timestamp('expires_at');
            $table->timestamp('redeemed_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')
                ->references('id')->on('tenants')
                ->onDelete('cascade');
            $table->index(['tenant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('activation_codes');
    }
};
```

- [ ] **Step 2: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivationCode extends Model
{
    protected $connection = 'central';
    protected $table = 'activation_codes';

    protected $fillable = [
        'tenant_id',
        'code',
        'plan',
        'intended_admin_name',
        'intended_admin_email',
        'status',
        'expires_at',
        'redeemed_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at'  => 'datetime',
            'redeemed_at' => 'datetime',
            'revoked_at'  => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Generate a unique 12-char code formatted XXXX-YYYY-ZZZZ from an
     * unambiguous 32-char alphabet (no 0/O, no 1/I).
     */
    public static function generate(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $alphaLen = strlen($alphabet);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $chars = '';
            for ($i = 0; $i < 12; $i++) {
                $chars .= $alphabet[random_int(0, $alphaLen - 1)];
            }
            $candidate = substr($chars, 0, 4) . '-' . substr($chars, 4, 4) . '-' . substr($chars, 8, 4);

            if (! self::where('code', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Could not generate a unique activation code after 5 attempts.');
    }

    public function isRedeemable(): bool
    {
        return $this->status === 'unredeemed' && $this->expires_at->isFuture();
    }
}
```

- [ ] **Step 3: Add `activationCodes()` and `currentUnredeemedCode()` to `Tenant` model**

Open `src/app/Models/Tenant.php`. Add inside the class (after the existing `provisioningJobs()` relation):

```php
    public function activationCodes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ActivationCode::class);
    }

    public function currentUnredeemedCode(): ?\App\Models\ActivationCode
    {
        return $this->activationCodes()
            ->where('status', 'unredeemed')
            ->where('expires_at', '>', now())
            ->latest()
            ->first();
    }
```

- [ ] **Step 4: Run the migration**

```bash
docker exec tp-app php artisan migrate --path=database/migrations/central --database=central
```

Expected: `2026_04_19_000003_create_activation_codes_table` marked DONE.

- [ ] **Step 5: Verify the model + generate**

```bash
docker exec tp-app php artisan tinker --execute="
\$code = App\Models\ActivationCode::generate();
echo 'Generated: ' . \$code . PHP_EOL;
echo 'Length: ' . strlen(\$code) . PHP_EOL;
echo 'Pattern OK: ' . (preg_match('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}\$/', \$code) ? 'yes' : 'no') . PHP_EOL;
"
```

Expected: prints a code like `XXXX-YYYY-ZZZZ` of length 14, pattern OK = yes.

- [ ] **Step 6: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/database/migrations/central/2026_04_19_000003_create_activation_codes_table.php src/app/Models/ActivationCode.php src/app/Models/Tenant.php
git commit -m "feat(activation): activation_codes table, ActivationCode model, Tenant relations"
```

---

## Task 3: Wizard rewrite + ProvisionTenantJob simplification + new created.blade

**Files:**
- Modify: `src/app/Jobs/ProvisionTenantJob.php`
- Modify: `src/app/Http/Controllers/SuperAdmin/TenantController.php` (rewrite `store()`)
- Modify: `src/resources/views/super-admin/tenants/create.blade.php` (add plan picker)
- Replace: `src/resources/views/super-admin/tenants/created.blade.php` (was temp-password reveal; now code reveal)

- [ ] **Step 1: Simplify `ProvisionTenantJob`**

Replace the entire file `src/app/Jobs/ProvisionTenantJob.php` with:

```php
<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantProvisioningJob;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Provisions a tenant DB. Caller decides what status to set on the tenant
 * after success — typically `pending_activation` for fresh wizard creations
 * (the school admin will set their password via the activation flow).
 */
class ProvisionTenantJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

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

        $centralConfig = config('database.connections.central');
        Config::set('database.connections._tenant_provisioner', array_merge($centralConfig, ['database' => null]));
        DB::purge('_tenant_provisioner');

        DB::connection('_tenant_provisioner')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $tenantUser = config('database.connections.mysql.username');
        DB::connection('_tenant_provisioner')->statement(
            "GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$tenantUser}'@'%'"
        );
        DB::connection('_tenant_provisioner')->statement('FLUSH PRIVILEGES');
    }

    protected function runTenantMigrations(): void
    {
        Artisan::call('tenants:migrate', [
            '--tenants' => [(string) $this->tenant->id],
            '--force'   => true,
        ]);
    }

    protected function seedTemplate(): void
    {
        Artisan::call('tenants:seed', [
            '--tenants' => [(string) $this->tenant->id],
            '--class'   => 'Database\\Seeders\\TenantTemplateSeeder',
            '--force'   => true,
        ]);
    }
}
```

(Removed: `createFirstAdmin()`, the constructor's admin* params, the success-side status update. The job now only owns DB setup + audit log.)

- [ ] **Step 2: Add the plan picker to `create.blade.php`**

Open `src/resources/views/super-admin/tenants/create.blade.php`. Find the closing `</hr>` separator before the admin name field. After the existing `admin_name` and `admin_email` blocks (and before the submit row), add a NEW `<hr>` + plan picker block:

```php
        <hr class="border-slate-200">

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Plan</label>
            <div class="grid grid-cols-3 gap-3">
                @foreach (config('plans') as $slug => $plan)
                    <label class="cursor-pointer rounded-lg border-2 border-slate-200 p-4 hover:border-slate-400 has-[:checked]:border-slate-900 has-[:checked]:bg-slate-50">
                        <input type="radio" name="plan" value="{{ $slug }}" {{ old('plan', 'free') === $slug ? 'checked' : '' }} class="sr-only">
                        <div class="font-semibold text-slate-900">{{ $plan['name'] }}</div>
                        <div class="text-xs text-slate-500 mt-1">
                            @if (is_numeric($plan['price']))
                                ${{ $plan['price'] }} {{ $plan['period'] }}
                            @else
                                {{ $plan['price'] }}
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
            @error('plan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>
```

- [ ] **Step 3: Replace `TenantController@store` and add plan validation**

Open `src/app/Http/Controllers/SuperAdmin/TenantController.php`. Replace the `store()` method and add `use App\Models\ActivationCode;` to the imports at the top (after the existing `use App\Models\Tenant;` line). The full new method:

```php
    public function store(Request $request): RedirectResponse|\Illuminate\View\View
    {
        $validPlans = array_keys(config('plans'));

        $data = $request->validate([
            'name'        => ['required', 'string', 'max:120'],
            'subdomain'   => ['required', 'string', new AvailableSubdomain()],
            'admin_name'  => ['required', 'string', 'max:120'],
            'admin_email' => ['required', 'email', 'max:255'],
            'plan'        => ['required', 'string', \Illuminate\Validation\Rule::in($validPlans)],
        ]);

        $tenant = Tenant::create([
            'name'      => $data['name'],
            'subdomain' => strtolower($data['subdomain']),
            'database'  => 'tenant_' . Str::random(8),
            'status'    => 'provisioning',
            'plan'      => $data['plan'],
        ]);

        $tenant->domains()->create(['domain' => strtolower($data['subdomain'])]);

        $tenant->update(['database' => 'tenant_' . $tenant->id]);
        $tenant->refresh();

        try {
            (new ProvisionTenantJob($tenant))->handle();
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.tenants.show', $tenant)
                ->with('error', 'Provisioning failed: ' . $e->getMessage());
        }

        $tenant->update(['status' => 'pending_activation']);

        $activationCode = ActivationCode::create([
            'tenant_id'            => $tenant->id,
            'code'                 => ActivationCode::generate(),
            'plan'                 => $data['plan'],
            'intended_admin_name'  => $data['admin_name'],
            'intended_admin_email' => $data['admin_email'],
            'status'               => 'unredeemed',
            'expires_at'           => now()->addDays(30),
        ]);

        return view('super-admin.tenants.created', [
            'tenant'         => $tenant->refresh(),
            'activationCode' => $activationCode,
        ]);
    }
```

- [ ] **Step 4: Replace `created.blade.php` (now shows the code, not the password)**

Replace the entire file `src/resources/views/super-admin/tenants/created.blade.php` with:

```php
@extends('super-admin.layout', ['title' => 'School created'])

@section('content')
<div class="max-w-2xl bg-white shadow rounded-lg p-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-lg">✓</div>
        <h1 class="text-xl font-semibold text-slate-900">{{ $tenant->name }} is provisioned.</h1>
    </div>

    <p class="text-sm text-slate-600 mb-6">
        The school's database is ready. Send the activation code below to <code class="font-mono">{{ $activationCode->intended_admin_email }}</code> — they'll redeem it to set their own password and finish onboarding.
    </p>

    <dl class="space-y-4 text-sm mb-6">
        <div>
            <dt class="text-slate-500 mb-1">Activation code (shown once)</dt>
            <dd class="font-mono text-2xl tracking-wider select-all bg-yellow-50 border border-yellow-200 rounded px-4 py-3 text-center">{{ $activationCode->code }}</dd>
        </div>
        <div>
            <dt class="text-slate-500 mb-1">Activation URL</dt>
            <dd class="text-slate-900 font-mono text-xs select-all">{{ url('/activate?code=' . $activationCode->code) }}</dd>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-slate-500 mb-1">Plan</dt>
                <dd class="text-slate-900 font-medium">{{ config('plans.' . $activationCode->plan . '.name') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 mb-1">Expires</dt>
                <dd class="text-slate-900">{{ $activationCode->expires_at->toDayDateTimeString() }}</dd>
            </div>
        </div>
        <div>
            <dt class="text-slate-500 mb-1">Intended admin</dt>
            <dd class="text-slate-900">{{ $activationCode->intended_admin_name }} &lt;{{ $activationCode->intended_admin_email }}&gt;</dd>
        </div>
    </dl>

    <p class="text-sm text-slate-500 mb-6">If they lose this code, you can revoke + regenerate from the school's detail page.</p>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to schools</a>
        <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Open school detail
        </a>
    </div>
</div>
@endsection
```

- [ ] **Step 5: Verify wizard end-to-end via tinker (no browser needed)**

```bash
docker exec tp-app php artisan tinker --execute="
use App\Jobs\ProvisionTenantJob;
use App\Models\Tenant;
use App\Models\ActivationCode;
use Illuminate\Support\Str;

\$t = Tenant::create([
    'name' => 'Plan Test School',
    'subdomain' => 'plantest',
    'database' => 'tenant_smoke',
    'status' => 'provisioning',
    'plan' => 'pro',
]);
\$t->domains()->create(['domain' => 'plantest']);
\$t->update(['database' => 'tenant_' . \$t->id]);
\$t->refresh();

(new ProvisionTenantJob(\$t))->handle();
\$t->update(['status' => 'pending_activation']);

\$code = ActivationCode::create([
    'tenant_id' => \$t->id,
    'code' => ActivationCode::generate(),
    'plan' => 'pro',
    'intended_admin_name' => 'Test Admin',
    'intended_admin_email' => 'test@plantest.test',
    'status' => 'unredeemed',
    'expires_at' => now()->addDays(30),
]);

echo 'Tenant id=' . \$t->id . ', status=' . \$t->status . ', plan=' . \$t->plan . PHP_EOL;
echo 'Code: ' . \$code->code . PHP_EOL;
echo 'Redeemable: ' . (\$code->isRedeemable() ? 'yes' : 'no') . PHP_EOL;

// Verify NO user was created in the tenant DB
tenancy()->initialize(\$t);
echo 'Tenant DB users: ' . DB::table('users')->count() . PHP_EOL;
tenancy()->end();

// Cleanup
DB::statement('DROP DATABASE IF EXISTS `tenant_' . \$t->id . '`');
\$code->delete();
\$t->domains()->delete();
\$t->delete();
echo 'cleaned' . PHP_EOL;
" 2>&1 | tail -10
```

Expected: tenant created, status=`pending_activation`, plan=`pro`, code generated, `Redeemable: yes`, **`Tenant DB users: 0`** (proving no user was auto-created).

- [ ] **Step 6: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Jobs/ProvisionTenantJob.php src/app/Http/Controllers/SuperAdmin/TenantController.php src/resources/views/super-admin/tenants/create.blade.php src/resources/views/super-admin/tenants/created.blade.php
git commit -m "feat(activation): wizard generates activation code, ProvisionTenantJob owns DB only"
```

---

## Task 4: Activation page (controller + 3 views + routes)

**Files:**
- Create: `src/app/Http/Controllers/Central/ActivationController.php`
- Create: `src/resources/views/central/activate/show.blade.php`
- Create: `src/resources/views/central/activate/invalid.blade.php`
- Create: `src/resources/views/central/activate/success.blade.php`
- Modify: `src/routes/web.php` (register `/activate` per central domain)

- [ ] **Step 1: Write `ActivationController.php`**

Create the directory `src/app/Http/Controllers/Central/` if it doesn't exist. Write:

```php
<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivationController extends Controller
{
    public function show(Request $request): View
    {
        $codeParam = $request->query('code');

        if (! $codeParam) {
            return view('central.activate.show', [
                'code'   => null,
                'tenant' => null,
            ]);
        }

        $code = ActivationCode::with('tenant')->where('code', $codeParam)->first();

        if (! $code) {
            return view('central.activate.invalid', [
                'reason' => "We couldn't find that code. Double-check the value or contact your platform administrator.",
            ]);
        }

        return match ($code->status) {
            'redeemed' => view('central.activate.invalid', [
                'reason' => 'This code was already used. If you need a new one, contact your platform administrator.',
            ]),
            'revoked' => view('central.activate.invalid', [
                'reason' => 'This code was revoked. Contact your platform administrator for a fresh one.',
            ]),
            'expired' => view('central.activate.invalid', [
                'reason' => 'This code expired on ' . $code->expires_at->toDayDateTimeString() . '. Contact your platform administrator to regenerate.',
            ]),
            'unredeemed' => $code->expires_at->isPast()
                ? view('central.activate.invalid', [
                    'reason' => 'This code expired on ' . $code->expires_at->toDayDateTimeString() . '. Contact your platform administrator to regenerate.',
                ])
                : view('central.activate.show', [
                    'code'   => $code,
                    'tenant' => $code->tenant,
                ]),
        };
    }

    public function submit(Request $request): View|RedirectResponse
    {
        $data = $request->validate([
            'code'     => ['required', 'string', 'regex:/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $code = ActivationCode::with('tenant')->where('code', $data['code'])->first();

        if (! $code || ! $code->isRedeemable()) {
            return back()
                ->withInput($request->only('code'))
                ->withErrors([
                    'code' => 'This code cannot be redeemed. It may have been used, revoked, or expired.',
                ]);
        }

        $tenant = $code->tenant;
        if ($tenant->status !== 'pending_activation') {
            return back()
                ->withInput($request->only('code'))
                ->withErrors([
                    'code' => "This school's status is '{$tenant->status}' — activation is no longer accepted.",
                ]);
        }

        tenancy()->initialize($tenant);

        try {
            \App\Models\User::create([
                'name'                 => $code->intended_admin_name,
                'email'                => $code->intended_admin_email,
                'password'             => $data['password'],
                'roles'                => ['admin'],
                'is_active'            => true,
                'must_change_password' => false,
            ]);
        } catch (UniqueConstraintViolationException $e) {
            tenancy()->end();
            return back()
                ->withInput($request->only('code'))
                ->withErrors([
                    'code' => 'An admin user already exists for this school. Contact the platform operator.',
                ]);
        } finally {
            if (tenant() !== null) {
                tenancy()->end();
            }
        }

        $code->update(['status' => 'redeemed', 'redeemed_at' => now()]);
        $tenant->update(['status' => 'active']);

        return view('central.activate.success', [
            'tenant'      => $tenant->refresh(),
            'adminEmail'  => $code->intended_admin_email,
            'loginUrl'    => 'http://' . $tenant->subdomain . '.localhost:8081/login',
        ]);
    }
}
```

- [ ] **Step 2: Write `show.blade.php`**

Create the directory `src/resources/views/central/activate/` if needed. Write:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate school — Teachers Performance Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-semibold text-slate-900 mb-1">Activate your school</h1>
        @if ($tenant)
            <p class="text-sm text-slate-600 mb-6">
                <strong class="text-slate-900">{{ $tenant->name }}</strong> is ready. Set a password for
                <code class="font-mono">{{ $code->intended_admin_email }}</code> to finish onboarding.
            </p>
        @else
            <p class="text-sm text-slate-600 mb-6">Enter your activation code to set a password and sign in.</p>
        @endif

        <form method="POST" action="{{ route('central.activate.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 mb-1">Activation code</label>
                <input id="code" name="code" type="text" required
                    value="{{ old('code', $code?->code) }}"
                    pattern="[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}"
                    placeholder="XXXX-YYYY-ZZZZ"
                    autofocus
                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 font-mono uppercase">
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($code)
                <div class="rounded-md bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-600">
                    Activating as: <code class="font-mono">{{ $code->intended_admin_email }}</code> ({{ $code->intended_admin_name }})
                </div>
            @endif

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Choose a password</label>
                <input id="password" name="password" type="password" required minlength="8"
                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <p class="mt-1 text-xs text-slate-500">At least 8 characters.</p>
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            </div>

            <button type="submit" class="w-full rounded-md bg-slate-900 text-white py-2 hover:bg-slate-800">
                Activate school
            </button>
        </form>
    </div>
</body>
</html>
```

- [ ] **Step 3: Write `invalid.blade.php`**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Code not valid — Teachers Performance Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md bg-white shadow rounded-lg p-8 text-center">
        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-700 text-2xl mx-auto mb-4">!</div>
        <h1 class="text-xl font-semibold text-slate-900 mb-2">This code can't be used</h1>
        <p class="text-sm text-slate-600 mb-6">{{ $reason }}</p>
        <a href="{{ route('central.activate.show') }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">
            Try a different code
        </a>
    </div>
</body>
</html>
```

- [ ] **Step 4: Write `success.blade.php`**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $tenant->name }} — activated</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white shadow rounded-lg p-8 text-center">
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-2xl mx-auto mb-4">✓</div>
        <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ $tenant->name }} is ready</h1>
        <p class="text-sm text-slate-600 mb-6">
            Sign in as <code class="font-mono">{{ $adminEmail }}</code> with the password you just set.
        </p>
        <a href="{{ $loginUrl }}" class="inline-flex items-center rounded-md bg-slate-900 px-6 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Open {{ $tenant->subdomain }}.localhost
        </a>
    </div>
</body>
</html>
```

- [ ] **Step 5: Register the routes in `web.php`**

Open `src/routes/web.php`. After the existing `foreach` loop that registers the landing per central domain, add a SECOND foreach that registers the activation routes the same way:

```php
foreach (config('tenancy.central_domains', []) as $centralDomain) {
    if ($centralDomain === $adminDomain) {
        continue;
    }

    Route::domain($centralDomain)->group(function () {
        Route::get('/activate', [\App\Http\Controllers\Central\ActivationController::class, 'show'])
            ->name('central.activate.show');
        Route::post('/activate', [\App\Http\Controllers\Central\ActivationController::class, 'submit'])
            ->middleware('throttle:5,1')
            ->name('central.activate.submit');
    });
}
```

Note: only the FIRST registration per name binds (Laravel uses the first definition for `route()` URL generation). Domain-specific groups still match by host. So `route('central.activate.submit')` returns the URL for the first central domain (`localhost`), which is what we want.

- [ ] **Step 6: Verify the routes**

```bash
docker exec tp-app php artisan route:clear
docker exec tp-app php artisan route:list 2>&1 | grep activate
```

Expected: shows `central.activate.show` (GET) and `central.activate.submit` (POST) on `localhost`.

```bash
curl -sS -o /dev/null -w "GET /activate → HTTP %{http_code}\n" http://localhost:8081/activate
```

Expected: HTTP 200 (renders the empty form).

- [ ] **Step 7: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Http/Controllers/Central/ActivationController.php src/resources/views/central/activate/ src/routes/web.php
git commit -m "feat(activation): public /activate endpoint — show, redeem, success, invalid"
```

---

## Task 5: Update `EnsureTenantIsActive` middleware + `suspended.blade` copy switch

**Files:**
- Modify: `src/app/Http/Middleware/EnsureTenantIsActive.php`
- Modify: `src/resources/views/tenancy/suspended.blade.php`

- [ ] **Step 1: Update the middleware**

Open `src/app/Http/Middleware/EnsureTenantIsActive.php`. The current file checks for `status !== 'active'` and renders `tenancy.suspended` with a 403. Keep it the same — the only change is the view itself adapts.

(No code change in this step; explicit no-op so the next step makes sense.)

- [ ] **Step 2: Update `suspended.blade.php` to switch copy on status**

Replace the entire file `src/resources/views/tenancy/suspended.blade.php` with:

```php
@php
    $messages = [
        'suspended'           => 'This school is currently suspended. Contact your platform administrator if this is unexpected.',
        'pending_activation'  => 'This school hasn\'t been activated yet. Visit ' . url('/', secure: false) . '/activate on the platform to redeem your code.',
        'failed'              => 'This school failed to provision. Contact your platform administrator.',
        'provisioning'        => 'This school is being set up. Please try again in a moment.',
    ];
    $message = $messages[$tenant->status] ?? 'This school is unavailable right now.';

    $titleStatus = match ($tenant->status) {
        'pending_activation' => 'awaiting activation',
        default              => $tenant->status,
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $tenant->name }} — {{ $titleStatus }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white shadow rounded-lg p-8 max-w-md text-center">
        <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ $tenant->name }} is {{ $titleStatus }}.</h1>
        <p class="text-sm text-slate-600">{{ $message }}</p>

        @if ($tenant->status === 'pending_activation')
            <a href="{{ url('http://localhost:8081/activate') }}" class="mt-6 inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Go to activation
            </a>
        @endif
    </div>
</body>
</html>
```

(Note: the `url('http://localhost:8081/activate')` is hardcoded for dev. In production it would be your platform's central domain. For the capstone this is fine.)

- [ ] **Step 3: Verify**

Manually flip JCD to `pending_activation` and curl:

```bash
docker exec tp-app php artisan tinker --execute="App\Models\Tenant::find(1)->update(['status' => 'pending_activation']);"
curl -sS http://jcd.localhost:8081/login -w "\nHTTP %{http_code}\n" 2>&1 | tail -5
docker exec tp-app php artisan tinker --execute="App\Models\Tenant::find(1)->update(['status' => 'active']);"
curl -sS -o /dev/null -w "After flip back: HTTP %{http_code}\n" http://jcd.localhost:8081/login
```

Expected: first curl returns 403 with the "awaiting activation" copy + activation button. Second curl returns 200 (JCD restored).

- [ ] **Step 4: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/resources/views/tenancy/suspended.blade.php
git commit -m "feat(activation): suspended view switches copy on tenant status"
```

---

## Task 6: Public landing rewrite (pricing + how it works)

**Files:**
- Replace: `src/resources/views/central/landing.blade.php`

- [ ] **Step 1: Replace the landing**

Replace the entire file `src/resources/views/central/landing.blade.php` with:

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teachers Performance — Multi-tenant evaluation platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

    {{-- Hero --}}
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-20 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-4">Teachers Performance Platform</h1>
            <p class="text-lg text-slate-600 mb-8 max-w-2xl mx-auto">
                Multi-school faculty evaluation, peer review, and AI-powered performance insights — one console for every campus.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('central.activate.show') }}" class="inline-flex items-center rounded-md bg-slate-900 px-6 py-3 text-sm font-medium text-white hover:bg-slate-800">
                    Got an activation code? → Activate
                </a>
                <a href="mailto:sales@platform.test" class="inline-flex items-center rounded-md border border-slate-300 px-6 py-3 text-sm text-slate-700 hover:bg-slate-50">
                    Contact sales
                </a>
            </div>
        </div>
    </header>

    {{-- Pricing --}}
    <section class="py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-semibold text-slate-900 mb-2">Plans for every school</h2>
                <p class="text-slate-600">Simple tiers. Switch or upgrade anytime.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach (config('plans') as $slug => $plan)
                    <div class="relative bg-white rounded-lg shadow border-2 {{ $plan['highlight'] ? 'border-slate-900' : 'border-transparent' }} p-8 flex flex-col">
                        @if ($plan['highlight'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-medium text-white">Most popular</span>
                        @endif

                        <h3 class="text-xl font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                        <p class="text-sm text-slate-500 mt-1 mb-4">{{ $plan['tagline'] }}</p>

                        <div class="mb-6">
                            @if (is_numeric($plan['price']))
                                <span class="text-4xl font-bold text-slate-900">${{ $plan['price'] }}</span>
                                <span class="text-slate-500 text-sm ml-1">{{ $plan['period'] }}</span>
                            @else
                                <span class="text-4xl font-bold text-slate-900">{{ $plan['price'] }}</span>
                            @endif
                        </div>

                        <ul class="space-y-2 text-sm text-slate-700 mb-8 flex-1">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-start gap-2">
                                    <span class="text-green-600 flex-shrink-0">✓</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <a href="mailto:sales@platform.test?subject=Interested in the {{ $plan['name'] }} plan"
                           class="inline-flex items-center justify-center rounded-md {{ $plan['highlight'] ? 'bg-slate-900 text-white hover:bg-slate-800' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }} px-4 py-2 text-sm font-medium">
                            Get started — contact sales
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-white border-y border-slate-200 py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-semibold text-slate-900 mb-2">How onboarding works</h2>
                <p class="text-slate-600">Sales-led, fast to spin up.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center mx-auto mb-4">1</div>
                    <h3 class="font-semibold text-slate-900 mb-1">We provision your school</h3>
                    <p class="text-sm text-slate-600">A dedicated tenant database with the full schema, ready in seconds.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center mx-auto mb-4">2</div>
                    <h3 class="font-semibold text-slate-900 mb-1">You receive your activation code</h3>
                    <p class="text-sm text-slate-600">A one-time 12-char code, valid for 30 days, sent to your school's first admin.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center mx-auto mb-4">3</div>
                    <h3 class="font-semibold text-slate-900 mb-1">Sign in with your chosen password</h3>
                    <p class="text-sm text-slate-600">Redeem the code, set your own password, and you're in.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-8 text-center text-sm text-slate-500">
        <p>&copy; {{ date('Y') }} Teachers Performance Platform.
        <a href="{{ route('central.activate.show') }}" class="text-slate-700 hover:text-slate-900 underline">Have a code? Activate here.</a></p>
    </footer>

</body>
</html>
```

- [ ] **Step 2: Verify**

```bash
docker exec tp-app php artisan view:clear
curl -sS http://localhost:8081/ -o /tmp/landing.html -w "HTTP %{http_code}\n"
grep -c "Most popular" /tmp/landing.html
grep -c "Activate" /tmp/landing.html
```

Expected: HTTP 200; "Most popular" appears once (Pro card); "Activate" appears at least twice (hero CTA + footer link).

- [ ] **Step 3: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/resources/views/central/landing.blade.php
git commit -m "feat(activation): public landing — hero, 3-tier pricing, how it works"
```

---

## Task 7: Plans dashboard + tenants index/show enrichment + revoke/regenerate

**Files:**
- Create: `src/app/Http/Controllers/SuperAdmin/PlanController.php`
- Create: `src/resources/views/super-admin/plans/index.blade.php`
- Modify: `src/app/Http/Controllers/SuperAdmin/TenantController.php` — add `regenerateCode()`, `revokeCode()`, filter `index()`
- Modify: `src/resources/views/super-admin/tenants/index.blade.php` — add Plan column + filters UI
- Modify: `src/resources/views/super-admin/tenants/show.blade.php` — add Activation section
- Modify: `src/resources/views/super-admin/layout.blade.php` — add "Plans" nav link
- Modify: `src/routes/admin.php` — add `/plans` + `/codes/regenerate` + `/codes/{code}/revoke` routes

- [ ] **Step 1: Add the routes in `routes/admin.php`**

Inside the existing `Route::middleware('auth:super_admin')->group(function () {` block, add:

```php
    Route::get('/plans', [\App\Http\Controllers\SuperAdmin\PlanController::class, 'index'])
        ->name('admin.plans.index');

    Route::post('/tenants/{tenant}/codes/regenerate', [TenantController::class, 'regenerateCode'])
        ->name('admin.tenants.codes.regenerate');
    Route::post('/tenants/{tenant}/codes/{code}/revoke', [TenantController::class, 'revokeCode'])
        ->name('admin.tenants.codes.revoke');
```

- [ ] **Step 2: Write `PlanController.php`**

```php
<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlanController extends Controller
{
    public function index(Request $request): View
    {
        $plans = config('plans');

        $counts = Tenant::where('status', 'active')
            ->selectRaw('plan, COUNT(*) as count')
            ->groupBy('plan')
            ->pluck('count', 'plan')
            ->all();

        $statusFilter = $request->query('status', 'all');
        $codesQuery = ActivationCode::with('tenant')->orderByDesc('created_at');

        if ($statusFilter === 'all') {
            // hide redeemed by default to focus on actionable rows
            $codesQuery->whereIn('status', ['unredeemed', 'revoked', 'expired']);
        } elseif (in_array($statusFilter, ['unredeemed', 'redeemed', 'revoked', 'expired'], true)) {
            $codesQuery->where('status', $statusFilter);
        }

        $codes = $codesQuery->limit(50)->get();

        return view('super-admin.plans.index', [
            'plans'        => $plans,
            'counts'       => $counts,
            'codes'        => $codes,
            'statusFilter' => $statusFilter,
        ]);
    }
}
```

- [ ] **Step 3: Write `super-admin/plans/index.blade.php`**

```php
@extends('super-admin.layout', ['title' => 'Plans'])

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Plans</h1>
    <p class="text-sm text-slate-500">Plan distribution across active schools and outstanding activation codes.</p>
</div>

<div class="grid md:grid-cols-3 gap-4 mb-8">
    @foreach ($plans as $slug => $plan)
        <div class="bg-white shadow rounded-lg p-6 {{ $plan['highlight'] ? 'border-2 border-slate-900' : '' }}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $plan['tagline'] }}</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">{{ $counts[$slug] ?? 0 }}</span>
            </div>
            <p class="text-xs text-slate-500 mb-3">
                @if (is_numeric($plan['price']))
                    ${{ $plan['price'] }} {{ $plan['period'] }}
                @else
                    {{ $plan['price'] }}
                @endif
            </p>
            <a href="{{ route('admin.tenants.index', ['plan' => $slug]) }}" class="text-sm text-slate-700 hover:text-slate-900">View schools →</a>
        </div>
    @endforeach
</div>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Activation codes</h2>
        <div class="flex items-center gap-2 text-sm">
            <span class="text-slate-500">Filter:</span>
            @foreach (['all' => 'Active (no redeemed)', 'unredeemed' => 'Unredeemed', 'redeemed' => 'Redeemed', 'revoked' => 'Revoked', 'expired' => 'Expired'] as $key => $label)
                <a href="{{ route('admin.plans.index', ['status' => $key]) }}"
                   class="px-2 py-1 rounded {{ $statusFilter === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Tenant</th>
                <th class="px-6 py-3">Code</th>
                <th class="px-6 py-3">Plan</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Generated</th>
                <th class="px-6 py-3">Expires</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-200 text-sm text-slate-700">
            @forelse ($codes as $code)
                <tr>
                    <td class="px-6 py-3"><a href="{{ route('admin.tenants.show', $code->tenant) }}" class="text-slate-900 hover:underline">{{ $code->tenant->name }}</a></td>
                    <td class="px-6 py-3"><code class="font-mono text-xs">{{ $code->code }}</code></td>
                    <td class="px-6 py-3">{{ config('plans.' . $code->plan . '.name', $code->plan) }}</td>
                    <td class="px-6 py-3">
                        @php
                            $color = match($code->status) {
                                'unredeemed' => 'bg-yellow-100 text-yellow-800',
                                'redeemed'   => 'bg-green-100 text-green-800',
                                'revoked'    => 'bg-slate-200 text-slate-700',
                                'expired'    => 'bg-red-100 text-red-800',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">{{ $code->status }}</span>
                    </td>
                    <td class="px-6 py-3 text-slate-500">{{ $code->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-slate-500">
                        @if ($code->status === 'unredeemed')
                            in {{ now()->diffInDays($code->expires_at) }} days
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right">
                        @if ($code->status === 'unredeemed')
                            <form method="POST" action="{{ route('admin.tenants.codes.revoke', [$code->tenant, $code]) }}" class="inline" onsubmit="return confirm('Revoke this code? It can no longer be redeemed.');">
                                @csrf
                                <button class="text-xs text-red-700 hover:text-red-900">Revoke</button>
                            </form>
                        @elseif (in_array($code->status, ['revoked', 'expired'], true))
                            <form method="POST" action="{{ route('admin.tenants.codes.regenerate', $code->tenant) }}" class="inline">
                                @csrf
                                <button class="text-xs text-slate-700 hover:text-slate-900">Regenerate</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">No codes match this filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
```

- [ ] **Step 4: Add `regenerateCode()` and `revokeCode()` to `TenantController.php`**

Open `src/app/Http/Controllers/SuperAdmin/TenantController.php`. Add `use App\Models\ActivationCode;` to the imports if not already present. Append these methods inside the class (before the closing `}`):

```php
    public function regenerateCode(Tenant $tenant): RedirectResponse
    {
        // Revoke any current unredeemed code(s)
        $tenant->activationCodes()
            ->where('status', 'unredeemed')
            ->update(['status' => 'revoked', 'revoked_at' => now()]);

        $previous = $tenant->activationCodes()->latest()->first();
        $plan = $previous?->plan ?? $tenant->plan;
        $intendedName = $previous?->intended_admin_name ?? 'Admin';
        $intendedEmail = $previous?->intended_admin_email ?? 'admin@' . $tenant->subdomain . '.test';

        $code = ActivationCode::create([
            'tenant_id'            => $tenant->id,
            'code'                 => ActivationCode::generate(),
            'plan'                 => $plan,
            'intended_admin_name'  => $intendedName,
            'intended_admin_email' => $intendedEmail,
            'status'               => 'unredeemed',
            'expires_at'           => now()->addDays(30),
        ]);

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('status', "New activation code: {$code->code}");
    }

    public function revokeCode(Tenant $tenant, ActivationCode $code): RedirectResponse
    {
        if ($code->tenant_id !== $tenant->id) {
            abort(404);
        }

        if ($code->status === 'unredeemed') {
            $code->update(['status' => 'revoked', 'revoked_at' => now()]);
        }

        return redirect()
            ->route('admin.tenants.show', $tenant)
            ->with('status', 'Code revoked.');
    }
```

- [ ] **Step 5: Add `?plan=` and `?status=` filters to `index()`**

Open `src/app/Http/Controllers/SuperAdmin/TenantController.php`. Replace the `index()` method with:

```php
    public function index(Request $request): View
    {
        $query = Tenant::orderByDesc('id');

        if ($plan = $request->query('plan')) {
            $query->where('plan', $plan);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $tenants = $query->get();
        $pendingCount = Tenant::where('status', 'pending_activation')->count();

        return view('super-admin.tenants.index', [
            'tenants'      => $tenants,
            'pendingCount' => $pendingCount,
            'planFilter'   => $plan ?? null,
            'statusFilter' => $status ?? null,
        ]);
    }
```

- [ ] **Step 6: Add Plan column + filter UI + pending pill to `tenants/index.blade.php`**

Open `src/resources/views/super-admin/tenants/index.blade.php`. Replace the entire file with:

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

@if ($pendingCount > 0)
    <div class="mb-4 flex items-center gap-3">
        <span class="text-xs text-slate-500 uppercase tracking-wide">Filter:</span>
        @if ($statusFilter === 'pending_activation')
            <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs text-white">{{ $pendingCount }} pending activation ✕</a>
        @else
            <a href="{{ route('admin.tenants.index', ['status' => 'pending_activation']) }}" class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs text-yellow-800 hover:bg-yellow-200">{{ $pendingCount }} pending activation</a>
        @endif

        @if ($planFilter)
            <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs text-white">plan: {{ $planFilter }} ✕</a>
        @endif
    </div>
@endif

<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Name</th>
                <th class="px-6 py-3">Subdomain</th>
                <th class="px-6 py-3">Plan</th>
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
                    <td class="px-6 py-3">
                        @php
                            $planColor = match($tenant->plan) {
                                'free'       => 'bg-slate-100 text-slate-700',
                                'pro'        => 'bg-blue-100 text-blue-800',
                                'enterprise' => 'bg-purple-100 text-purple-800',
                                default      => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $planColor }}">{{ config('plans.' . $tenant->plan . '.name', $tenant->plan) }}</span>
                    </td>
                    <td class="px-6 py-3">
                        @php
                            $color = match($tenant->status) {
                                'active'             => 'bg-green-100 text-green-800',
                                'provisioning'       => 'bg-yellow-100 text-yellow-800',
                                'pending_activation' => 'bg-yellow-100 text-yellow-800',
                                'suspended'          => 'bg-slate-200 text-slate-700',
                                'failed'             => 'bg-red-100 text-red-800',
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
                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No schools match the current filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
```

- [ ] **Step 7: Add Activation section to `tenants/show.blade.php`**

Open `src/resources/views/super-admin/tenants/show.blade.php`. Find the existing Provisioning history block. Add a NEW section IMMEDIATELY BEFORE it (after the existing tenant header card):

```php
@php $currentCode = $tenant->currentUnredeemedCode(); @endphp
@php $latestCode = $tenant->activationCodes()->latest()->first(); @endphp

<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Activation</h2>
    @if ($currentCode)
        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-slate-500 mb-1">Code (active)</dt>
                <dd class="font-mono text-lg tracking-wider select-all bg-yellow-50 border border-yellow-200 rounded px-3 py-2 inline-block">{{ $currentCode->code }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 mb-1">Activation URL</dt>
                <dd class="font-mono text-xs select-all">{{ url('/activate?code=' . $currentCode->code) }}</dd>
            </div>
            <div class="text-slate-500">
                Expires {{ $currentCode->expires_at->diffForHumans() }} —
                intended for <code class="font-mono">{{ $currentCode->intended_admin_email }}</code>
            </div>
        </dl>
        <div class="mt-4 flex gap-2">
            <form method="POST" action="{{ route('admin.tenants.codes.revoke', [$tenant, $currentCode]) }}" onsubmit="return confirm('Revoke + regenerate? The current code stops working.');">
                @csrf
                <button class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Revoke</button>
            </form>
            <form method="POST" action="{{ route('admin.tenants.codes.regenerate', $tenant) }}">
                @csrf
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Revoke + Regenerate</button>
            </form>
        </div>
    @elseif ($latestCode && $latestCode->status === 'redeemed')
        <p class="text-sm text-slate-700">
            Activated by <code class="font-mono">{{ $latestCode->intended_admin_email }}</code> on {{ $latestCode->redeemed_at->toDayDateTimeString() }}.
        </p>
    @elseif ($latestCode)
        <p class="text-sm text-slate-700 mb-3">Last code was {{ $latestCode->status }} ({{ $latestCode->code }}).</p>
        <form method="POST" action="{{ route('admin.tenants.codes.regenerate', $tenant) }}">
            @csrf
            <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Regenerate</button>
        </form>
    @else
        <p class="text-sm text-slate-500">No activation codes have been generated for this school.</p>
    @endif
</div>
```

- [ ] **Step 8: Add "Plans" nav link to `super-admin/layout.blade.php`**

Open `src/resources/views/super-admin/layout.blade.php`. Find the nav block:

```php
<nav class="flex items-center gap-6 text-sm">
    <a href="{{ route('admin.tenants.index') }}" class="hover:text-slate-300">Schools</a>
```

Add a Plans link right after Schools:

```php
<nav class="flex items-center gap-6 text-sm">
    <a href="{{ route('admin.tenants.index') }}" class="hover:text-slate-300">Schools</a>
    <a href="{{ route('admin.plans.index') }}" class="hover:text-slate-300">Plans</a>
```

- [ ] **Step 9: Verify**

```bash
docker exec tp-app php artisan route:clear
docker exec tp-app php artisan view:clear
docker exec tp-app php artisan route:list 2>&1 | grep -E "admin\.(plans|tenants\.codes)"
```

Expected: shows `admin.plans.index`, `admin.tenants.codes.regenerate`, `admin.tenants.codes.revoke`.

Smoke test the Plans page renders (must be authenticated; just check the redirect is correct):

```bash
curl -sS -o /dev/null -w "GET /plans → HTTP %{http_code} | location=%{redirect_url}\n" http://admin.localhost:8081/plans
```

Expected: 302 redirect to `http://admin.localhost:8081/login` (auth gate).

- [ ] **Step 10: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/app/Http/Controllers/SuperAdmin/PlanController.php src/app/Http/Controllers/SuperAdmin/TenantController.php src/resources/views/super-admin/plans/ src/resources/views/super-admin/tenants/index.blade.php src/resources/views/super-admin/tenants/show.blade.php src/resources/views/super-admin/layout.blade.php src/routes/admin.php
git commit -m "feat(activation): Plans dashboard, plan column on tenants index, activation section on show page, revoke+regenerate routes"
```

---

## Task 8: Three new feature tests

**Files:**
- Create: `src/tests/Feature/Activation/WizardCreatesActivationCodeTest.php`
- Create: `src/tests/Feature/Activation/ActivationFlowTest.php`
- Create: `src/tests/Feature/Activation/RegenerateCodeTest.php`

- [ ] **Step 1: Write `WizardCreatesActivationCodeTest`**

Create `src/tests/Feature/Activation/WizardCreatesActivationCodeTest.php`:

```php
<?php

namespace Tests\Feature\Activation;

use App\Models\ActivationCode;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WizardCreatesActivationCodeTest extends TestCase
{
    public function test_wizard_creates_pending_tenant_with_unredeemed_code_and_no_user(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();
        $subdomain = 'wztest' . substr(md5(uniqid()), 0, 6);

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Wizard Test School',
            'subdomain'   => $subdomain,
            'admin_name'  => 'Wizard Admin',
            'admin_email' => 'wadmin@wztest.test',
            'plan'        => 'pro',
        ]);

        $response->assertOk();
        $response->assertSee('is provisioned');

        $tenant = Tenant::where('subdomain', $subdomain)->firstOrFail();
        $this->assertSame('pending_activation', $tenant->status);
        $this->assertSame('pro', $tenant->plan);

        $code = $tenant->activationCodes()->latest()->first();
        $this->assertNotNull($code);
        $this->assertSame('unredeemed', $code->status);
        $this->assertSame('pro', $code->plan);
        $this->assertSame('wadmin@wztest.test', $code->intended_admin_email);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $code->code);

        // Critical: NO user should exist yet — the activation flow creates them
        $tenantDb = $tenant->getAttribute('database');
        $userCount = DB::connection('mysql')->getPdo()
            ->query("SELECT COUNT(*) FROM `{$tenantDb}`.users")->fetchColumn();
        $this->assertSame(0, (int) $userCount, 'Wizard must NOT create a user — that is the activation flow\'s job.');
    }

    public function test_wizard_rejects_invalid_plan_slug(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Bad Plan School',
            'subdomain'   => 'badplan' . substr(md5(uniqid()), 0, 4),
            'admin_name'  => 'X',
            'admin_email' => 'x@y.test',
            'plan'        => 'platinum',  // not in config/plans.php
        ]);

        $response->assertSessionHasErrors('plan');
    }
}
```

- [ ] **Step 2: Write `ActivationFlowTest`**

Create `src/tests/Feature/Activation/ActivationFlowTest.php`:

```php
<?php

namespace Tests\Feature\Activation;

use App\Models\ActivationCode;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivationFlowTest extends TestCase
{
    /**
     * Helper to create a tenant + DB + unredeemed code in one call.
     * Tests cleanup the tenant_DB themselves in their finally blocks.
     */
    private function provisionTestTenant(string $statusOverride = 'pending_activation', ?string $codeStatus = 'unredeemed'): array
    {
        $subdomain = 'aft' . substr(md5(uniqid()), 0, 8);
        $database = 'tenant_aft_' . substr(md5(uniqid()), 0, 8);

        DB::connection('central')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenant = Tenant::create([
            'name'      => 'Activation Test ' . $subdomain,
            'subdomain' => $subdomain,
            'database'  => $database,
            'status'    => $statusOverride,
            'plan'      => 'free',
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        \Artisan::call('tenants:migrate', ['--tenants' => [(string) $tenant->id], '--force' => true]);

        $code = ActivationCode::create([
            'tenant_id'            => $tenant->id,
            'code'                 => ActivationCode::generate(),
            'plan'                 => 'free',
            'intended_admin_name'  => 'Activation Tester',
            'intended_admin_email' => 'tester@' . $subdomain . '.test',
            'status'               => $codeStatus,
            'expires_at'           => $codeStatus === 'expired' ? now()->subDay() : now()->addDays(30),
            'redeemed_at'          => $codeStatus === 'redeemed' ? now() : null,
            'revoked_at'           => $codeStatus === 'revoked' ? now() : null,
        ]);

        return [$tenant, $code, $database];
    }

    private function cleanup(Tenant $tenant, string $database): void
    {
        try {
            $tenant->activationCodes()->delete();
            $tenant->domains()->delete();
            $tenant->delete();
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$database}`");
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    public function test_get_activate_with_valid_code_pre_fills_form(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant();
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('Activate your school');
            $response->assertSee($tenant->name);
            $response->assertSee($code->intended_admin_email);
            $response->assertSee($code->code);
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_redeemed_code_shows_invalid(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('active', 'redeemed');
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('already used');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_revoked_code_shows_invalid(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('pending_activation', 'revoked');
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('was revoked');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_expired_code_shows_invalid(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('pending_activation', 'unredeemed');
        // Force-expire by setting expires_at in the past
        $code->update(['expires_at' => now()->subDay()]);
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('expired');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_missing_code_shows_blank_form(): void
    {
        $response = $this->get('http://localhost/activate');

        $response->assertOk();
        $response->assertSee('Activate your school');
    }

    public function test_post_activate_redeems_code_creates_user_activates_tenant(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant();
        try {
            $response = $this->post('http://localhost/activate', [
                'code'                  => $code->code,
                'password'              => 'mySecure!Pass1',
                'password_confirmation' => 'mySecure!Pass1',
            ]);

            $response->assertOk();
            $response->assertSee('is ready');

            $tenant->refresh();
            $this->assertSame('active', $tenant->status);

            $code->refresh();
            $this->assertSame('redeemed', $code->status);
            $this->assertNotNull($code->redeemed_at);

            // User row was created in the tenant DB
            $userCount = DB::connection('mysql')->getPdo()
                ->query("SELECT COUNT(*) FROM `{$database}`.users WHERE email='{$code->intended_admin_email}'")->fetchColumn();
            $this->assertSame(1, (int) $userCount);
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_post_activate_with_mismatched_password_confirmation_rejects(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant();
        try {
            $response = $this->post('http://localhost/activate', [
                'code'                  => $code->code,
                'password'              => 'mySecure!Pass1',
                'password_confirmation' => 'somethingElse',
            ]);

            $response->assertSessionHasErrors('password');

            $tenant->refresh();
            $this->assertSame('pending_activation', $tenant->status);
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_post_activate_with_already_redeemed_code_rejects(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('active', 'redeemed');
        try {
            $response = $this->post('http://localhost/activate', [
                'code'                  => $code->code,
                'password'              => 'mySecure!Pass1',
                'password_confirmation' => 'mySecure!Pass1',
            ]);

            $response->assertSessionHasErrors('code');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }
}
```

- [ ] **Step 3: Write `RegenerateCodeTest`**

Create `src/tests/Feature/Activation/RegenerateCodeTest.php`:

```php
<?php

namespace Tests\Feature\Activation;

use App\Models\ActivationCode;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegenerateCodeTest extends TestCase
{
    public function test_regenerate_revokes_current_code_and_creates_a_new_one(): void
    {
        // Set up tenant with one unredeemed code
        $subdomain = 'regen' . substr(md5(uniqid()), 0, 6);
        $database = 'tenant_regen_' . substr(md5(uniqid()), 0, 6);
        DB::connection('central')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenant = Tenant::create([
            'name' => 'Regenerate Test',
            'subdomain' => $subdomain,
            'database' => $database,
            'status' => 'pending_activation',
            'plan' => 'pro',
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        $original = ActivationCode::create([
            'tenant_id' => $tenant->id,
            'code' => ActivationCode::generate(),
            'plan' => 'pro',
            'intended_admin_name' => 'Original Admin',
            'intended_admin_email' => 'orig@regen.test',
            'status' => 'unredeemed',
            'expires_at' => now()->addDays(30),
        ]);

        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        try {
            $response = $this->actingAs($admin, 'super_admin')
                ->post("http://admin.localhost/tenants/{$tenant->id}/codes/regenerate");

            $response->assertRedirect();

            $original->refresh();
            $this->assertSame('revoked', $original->status);
            $this->assertNotNull($original->revoked_at);

            $newCode = $tenant->activationCodes()->where('status', 'unredeemed')->latest()->first();
            $this->assertNotNull($newCode);
            $this->assertNotSame($original->code, $newCode->code);
            $this->assertSame('pro', $newCode->plan);
            $this->assertSame('orig@regen.test', $newCode->intended_admin_email);
        } finally {
            $tenant->activationCodes()->delete();
            $tenant->domains()->delete();
            $tenant->delete();
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$database}`");
        }
    }

    public function test_revoke_marks_code_revoked_without_creating_a_new_one(): void
    {
        $subdomain = 'revtest' . substr(md5(uniqid()), 0, 6);
        $database = 'tenant_rev_' . substr(md5(uniqid()), 0, 6);
        DB::connection('central')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenant = Tenant::create([
            'name' => 'Revoke Test',
            'subdomain' => $subdomain,
            'database' => $database,
            'status' => 'pending_activation',
            'plan' => 'free',
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        $code = ActivationCode::create([
            'tenant_id' => $tenant->id,
            'code' => ActivationCode::generate(),
            'plan' => 'free',
            'intended_admin_name' => 'X',
            'intended_admin_email' => 'x@rev.test',
            'status' => 'unredeemed',
            'expires_at' => now()->addDays(30),
        ]);

        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        try {
            $response = $this->actingAs($admin, 'super_admin')
                ->post("http://admin.localhost/tenants/{$tenant->id}/codes/{$code->id}/revoke");

            $response->assertRedirect();

            $code->refresh();
            $this->assertSame('revoked', $code->status);

            $unredeemedCount = $tenant->activationCodes()->where('status', 'unredeemed')->count();
            $this->assertSame(0, $unredeemedCount, 'Revoke should NOT create a replacement code');
        } finally {
            $tenant->activationCodes()->delete();
            $tenant->domains()->delete();
            $tenant->delete();
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$database}`");
        }
    }
}
```

- [ ] **Step 4: Run all three test files**

```bash
docker exec tp-app php artisan test --filter=Activation 2>&1 | tail -20
```

Expected: ~12 tests pass (2 wizard + 8 activation flow + 2 regenerate). Total ~2-3 minutes (the wizard test runs the full provisioning job; activation tests provision lighter test tenants).

- [ ] **Step 5: Re-run the full multi-tenancy + super-admin suites to verify no regressions**

```bash
docker exec tp-app php artisan test --filter='Tenancy|SuperAdmin|Activation' 2>&1 | tail -10
```

Expected: all tests pass — Tenancy 8 + SuperAdmin 7 + Activation ~12.

- [ ] **Step 6: Commit**

```bash
cd D:/codespaces/capstone/jcd/jcd-laravel
git add src/tests/Feature/Activation/
git commit -m "test(activation): wizard creates code, activation flow end-to-end, revoke+regenerate cycle"
```

---

## Task 9: Final smoke test — full activation flow in the browser

Manual verification that the complete UX works end-to-end.

- [ ] **Step 1: Restart app + clear caches**

```bash
docker compose restart app
docker exec tp-app php artisan config:clear
docker exec tp-app php artisan route:clear
docker exec tp-app php artisan view:clear
```

- [ ] **Step 2: Visit the public landing**

Open `http://localhost:8081/` in a browser. Expected: 3-tier pricing, "Got an activation code?" CTA in the hero, "How it works" section, footer link.

- [ ] **Step 3: Provision a school via the super-admin wizard**

Open `http://admin.localhost:8081/login`. Sign in as `super@platform.test` / `super123`. Click "New school" → fill in:
- Name: `Activation Demo`
- Subdomain: `actdemo`
- Admin name: `Activation Demo Admin`
- Admin email: `admin@actdemo.test`
- Plan: select **Pro**

Click "Provision school". Expected: ~10s wait, then a success page showing the activation code (e.g., `KMNJ-7P2Q-RTWX`), the activation URL, the plan, and the intended admin email.

- [ ] **Step 4: Visit the Plans page**

Click "Plans" in the nav. Expected: 3 plan cards with counts (Free=1 for JCD, Pro=0 because Activation Demo is still pending, Enterprise=0). The activation codes table shows the new unredeemed code at the top.

- [ ] **Step 5: Verify the school is blocked pre-activation**

Open `http://actdemo.localhost:8081/login` in a new tab. Expected: 403 with "Activation Demo is awaiting activation" + a button linking to the activation page.

- [ ] **Step 6: Redeem the code**

Open `http://localhost:8081/activate?code=<the code from step 3>` (or copy the activation URL from the wizard success page). The form should pre-fill with the code and show the intended email. Set a password (e.g., `password123`), confirm it, click "Activate school".

Expected: success page "Activation Demo is ready" with a button to the subdomain.

- [ ] **Step 7: Sign in to the new school**

Click the button (or visit `http://actdemo.localhost:8081/login`). Sign in as `admin@actdemo.test` with the password you just set. Expected: dashboard loads. No "must change password" prompt (since the school admin chose the password themselves).

- [ ] **Step 8: Verify status flips back in the super-admin dashboard**

Return to `http://admin.localhost:8081/tenants`. Expected: Activation Demo shows status `active`, plan `Pro`. Click View → the Activation section shows "Activated by admin@actdemo.test on …". The code on the Plans page now shows `redeemed`.

- [ ] **Step 9: Verify revoke + regenerate**

Suspend → resume cycle isn't relevant here, but exercise revoke + regenerate on a fresh tenant. From the super-admin dashboard, create another school (call it `Regen Demo`). On the success page, note the code. Then go to its detail page and click "Revoke + Regenerate" — confirm a new code appears in the success flash. The old code should now show as `revoked` on the Plans page.

- [ ] **Step 10: Verify suspended/pending copy variations**

Manually flip Activation Demo to suspended via tinker:

```bash
docker exec tp-app php artisan tinker --execute="App\Models\Tenant::where('subdomain', 'actdemo')->first()->update(['status' => 'suspended']);"
```

Visit `http://actdemo.localhost:8081/login`. Expected: 403 "Activation Demo is suspended" — different copy from the pending_activation case.

Restore:

```bash
docker exec tp-app php artisan tinker --execute="App\Models\Tenant::where('subdomain', 'actdemo')->first()->update(['status' => 'active']);"
```

- [ ] **Step 11: Cleanup demo tenants** (optional)

```bash
docker exec tp-app php artisan tinker --execute="
foreach (App\Models\Tenant::whereIn('subdomain', ['actdemo', 'regendemo'])->get() as \$t) {
    \$db = \$t->getAttribute('database');
    \$t->activationCodes()->delete();
    \$t->domains()->delete();
    App\Models\TenantProvisioningJob::where('tenant_id', \$t->id)->delete();
    \$t->delete();
    DB::statement('DROP DATABASE IF EXISTS \`' . \$db . '\`');
    echo 'cleaned: ' . \$db . PHP_EOL;
}
"
```

- [ ] **Step 12: No commit — manual verification step**

---

## Done — Verification Checklist

- [ ] `config/plans.php` defines Free / Pro / Enterprise tiers.
- [ ] `tenants.plan` column exists with default `free`; JCD grandfathered as Free.
- [ ] `tenants.status` enum accepts `pending_activation`.
- [ ] `activation_codes` table exists with the 11 columns from the spec.
- [ ] `ActivationCode::generate()` produces unique 12-char codes from a 32-char alphabet.
- [ ] Wizard now requires a plan selection; on submit, generates a code instead of a temp password.
- [ ] `created.blade.php` shows the code, activation URL, plan, expiration — once.
- [ ] `ProvisionTenantJob` no longer creates a user; the activation flow does.
- [ ] `localhost:8081/` shows the pricing landing.
- [ ] `localhost:8081/activate?code=...` pre-fills the form for valid codes; shows a clear error for invalid/revoked/redeemed/expired.
- [ ] POST `/activate` creates the user, marks code redeemed, marks tenant active.
- [ ] `admin.localhost:8081/plans` shows plan distribution + activation codes table with revoke/regenerate.
- [ ] Tenants index shows a Plan column + filter pills.
- [ ] Tenant show page has an Activation section with revoke/regenerate buttons.
- [ ] `EnsureTenantIsActive` middleware shows status-aware copy for suspended / pending_activation / failed / provisioning.
- [ ] All Phase 1+2+3 + new Activation feature tests pass.
- [ ] Manual smoke test (Task 9) succeeds end-to-end.

When all boxes are ticked, the activation codes + plans feature is complete.
