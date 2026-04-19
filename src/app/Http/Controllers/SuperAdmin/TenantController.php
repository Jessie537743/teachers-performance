<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantJob;
use App\Models\ActivationCode;
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
