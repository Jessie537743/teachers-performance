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
            'database'  => 'tenant_' . Str::random(8), // placeholder — replaced after insert
            'status'    => 'provisioning',
        ]);

        // Mirror the subdomain into the central `domains` table so the
        // stancl resolver (DomainTenantResolver) can find this tenant.
        $tenant->domains()->create(['domain' => strtolower($data['subdomain'])]);

        // Now that we have the auto-incremented id, set the canonical
        // database name. The placeholder above only existed to satisfy
        // the NOT NULL constraint on the initial insert.
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
            // Job already marked tenant 'failed' and recorded the error.
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
}
