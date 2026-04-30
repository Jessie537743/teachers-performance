<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantJob;
use App\Models\ActivationCode;
use App\Models\Tenant;
use App\Rules\AvailableSubdomain;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $query = Tenant::query()->orderByDesc('id');

        if ($plan = $request->query('plan')) {
            $query->where('plan', $plan);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subdomain', 'like', "%{$search}%");
            });
        }

        $tenants = $query->paginate(15)->withQueryString();

        // Single grouped query: status + plan counts in one round-trip.
        $statusCounts = Tenant::selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $planCounts = Tenant::selectRaw('plan, COUNT(*) as c')
            ->groupBy('plan')
            ->pluck('c', 'plan')
            ->all();

        $stats = [
            'total'              => array_sum($statusCounts),
            'active'             => $statusCounts['active'] ?? 0,
            'pending_activation' => $statusCounts['pending_activation'] ?? 0,
            'awaiting_payment'   => $statusCounts['awaiting_payment'] ?? 0,
            'failed'             => $statusCounts['failed'] ?? 0,
            'suspended'          => $statusCounts['suspended'] ?? 0,
        ];

        return view('super-admin.tenants.index', [
            'tenants'      => $tenants,
            'stats'        => $stats,
            'planCounts'   => $planCounts,
            'planFilter'   => $plan ?? null,
            'statusFilter' => $status ?? null,
            'search'       => $search,
        ]);
    }

    public function create(): View
    {
        return view('super-admin.tenants.create');
    }

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

    public function show(Tenant $tenant): View
    {
        // Eager-load to avoid N+1 in the view.
        $tenant->load([
            'activationCodes' => fn ($q) => $q->orderByDesc('id'),
            'provisioningJobs' => fn ($q) => $q->orderByDesc('id'),
            'subscriptions' => fn ($q) => $q->orderByDesc('id')->limit(20),
        ]);

        return view('super-admin.tenants.show', [
            'tenant' => $tenant,
            'jobs'   => $tenant->provisioningJobs,
        ]);
    }

    public function chargeNow(Tenant $tenant, \App\Services\BillingService $billing): RedirectResponse
    {
        if (! in_array($tenant->subscription_status, ['active', 'grace'], true)) {
            return back()->with('error', "Tenant subscription is '{$tenant->subscription_status}' — cannot charge.");
        }

        $sub = $billing->chargeNextPeriod($tenant);

        return redirect()->route('admin.tenants.show', $tenant)->with(
            'status',
            $sub->status === 'paid'
                ? "Charged {$sub->formatted_amount}. Next charge: " . $tenant->fresh()->next_charge_at?->toDayDateTimeString()
                : "Charge failed: {$sub->failure_reason}. Retry scheduled."
        );
    }

    public function cancelSubscription(Tenant $tenant, \App\Services\BillingService $billing): RedirectResponse
    {
        $billing->cancel($tenant);

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('status', "Subscription canceled. Access continues until {$tenant->current_period_end?->toDayDateTimeString()}.");
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

        try {
            // Wipe whatever partial DB state the failed run left behind so the
            // re-run starts clean.
            $this->dropTenantDatabase($tenant);

            $tenant->update(['status' => 'provisioning']);

            (new ProvisionTenantJob($tenant->fresh()))->handle();

            $tenant->update(['status' => 'pending_activation']);
        } catch (\Throwable $e) {
            Log::error('Tenant retry failed', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);

            return redirect()->route('admin.tenants.show', $tenant)
                ->with('error', 'Retry failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.tenants.show', $tenant)
            ->with('status', "Provisioning succeeded. {$tenant->name} is ready to activate.");
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        // Only allow deleting tenants that never completed provisioning. Active
        // tenants must be suspended first to prevent fat-fingered data loss.
        if ($tenant->status !== 'failed') {
            return redirect()->route('admin.tenants.show', $tenant)
                ->with('error', 'Only failed tenants can be deleted from this UI. Suspend the tenant first if you need to remove an active school.');
        }

        $name = $tenant->name;

        try {
            $this->dropTenantDatabase($tenant);

            // Cascade: provisioning history, codes, subscriptions, domains, then the row itself.
            $tenant->provisioningJobs()->delete();
            $tenant->activationCodes()->delete();
            $tenant->subscriptions()->delete();
            $tenant->domains()->delete();
            $tenant->delete();
        } catch (\Throwable $e) {
            Log::error('Tenant destroy failed', [
                'tenant_id' => $tenant->id,
                'error'     => $e->getMessage(),
            ]);

            return redirect()->route('admin.tenants.index')
                ->with('error', 'Delete failed: ' . $e->getMessage());
        }

        return redirect()->route('admin.tenants.index')
            ->with('status', "{$name} and its database have been removed.");
    }

    /**
     * Drops the per-tenant MySQL database. Idempotent — safe to call when the
     * DB never got created. Uses the central root credentials so it can issue
     * DDL statements regardless of the tenant's grant scope.
     */
    private function dropTenantDatabase(Tenant $tenant): void
    {
        $databaseName = $tenant->getAttribute('database');
        if (! $databaseName) {
            return;
        }

        $centralConfig = config('database.connections.central');
        Config::set('database.connections._tenant_dropper', array_merge($centralConfig, ['database' => null]));
        DB::purge('_tenant_dropper');

        DB::connection('_tenant_dropper')->statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }

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
}
