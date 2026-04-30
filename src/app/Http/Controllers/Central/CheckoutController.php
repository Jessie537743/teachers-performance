<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantJob;
use App\Mail\TenantActivationCodeMail;
use App\Models\ActivationCode;
use App\Models\Tenant;
use App\Rules\AvailableSubdomain;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $plans = config('plans');
        $slug = $request->query('plan', 'pro');
        $cycle = in_array($request->query('cycle'), ['monthly', 'yearly'], true)
            ? $request->query('cycle')
            : 'monthly';

        if (! isset($plans[$slug])) {
            return redirect('/');
        }

        // Enterprise is sales-led; bounce to mailto from the landing page instead.
        if ($slug === 'enterprise' || ! isset($plans[$slug]['prices']['monthly'])) {
            return redirect('/');
        }

        return view('central.subscribe.show', [
            'plan'     => $plans[$slug],
            'planSlug' => $slug,
            'cycle'    => $cycle,
        ]);
    }

    public function process(Request $request, BillingService $billing): RedirectResponse
    {
        $plans = config('plans');
        $payablePlans = array_keys(array_filter(
            $plans,
            fn ($p) => $p['slug'] !== 'enterprise' && isset($p['prices']['monthly'])
        ));

        $data = $request->validate([
            'plan'           => ['required', 'string', Rule::in($payablePlans)],
            'billing_cycle'  => ['required', 'string', Rule::in(['monthly', 'yearly'])],
            'name'           => ['required', 'string', 'max:120'],
            'subdomain'      => ['required', 'string', new AvailableSubdomain()],
            'admin_name'     => ['required', 'string', 'max:120'],
            'admin_email'    => ['required', 'email', 'max:255'],
            'card_name'      => ['required', 'string', 'max:120'],
            'card_number'    => ['required', 'string', 'regex:/^[0-9 ]{13,23}$/'],
            'card_expiry'    => ['required', 'string', 'regex:/^(0[1-9]|1[0-2])\/\d{2}$/'],
            'card_cvc'       => ['required', 'string', 'regex:/^\d{3,4}$/'],
        ]);

        // Simulated payment gate — always succeeds unless the test card "4000 0000 0000 0002"
        // (Stripe's classic decline number) is used. Lets the demo show both paths.
        if (preg_replace('/\s+/', '', $data['card_number']) === '4000000000000002') {
            return back()
                ->withInput($request->except(['card_number', 'card_cvc']))
                ->withErrors(['card_number' => 'Your card was declined. Try a different card.']);
        }

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
            return back()
                ->withInput($request->except(['card_number', 'card_cvc']))
                ->withErrors(['name' => 'Provisioning failed: ' . $e->getMessage()]);
        }

        $tenant->update(['status' => 'pending_activation']);

        // Start the recurring subscription (records first paid period + next_charge_at).
        $billing->startSubscription($tenant, $data['billing_cycle']);

        $code = ActivationCode::create([
            'tenant_id'            => $tenant->id,
            'code'                 => ActivationCode::generate(),
            'plan'                 => $data['plan'],
            'intended_admin_name'  => $data['admin_name'],
            'intended_admin_email' => $data['admin_email'],
            'status'               => 'unredeemed',
            'expires_at'           => now()->addDays(30),
        ]);

        $tenantUrl = $this->tenantUrl($tenant);
        Mail::to($data['admin_email'])->queue(new TenantActivationCodeMail($tenant, $code, $tenantUrl));

        return redirect()->route('central.subscribe.success', [
            'tenant' => $tenant->id,
        ]);
    }

    public function success(Request $request): View|RedirectResponse
    {
        $tenant = Tenant::find($request->query('tenant'));
        if (! $tenant) {
            return redirect('/');
        }

        $code = $tenant->activationCodes()->latest('id')->first();
        if (! $code) {
            return redirect('/');
        }

        return view('central.subscribe.success', [
            'tenant'        => $tenant,
            'maskedEmail'   => $this->maskEmail($code->intended_admin_email),
            'tenantUrl'     => $this->tenantUrl($tenant),
        ]);
    }

    private function tenantUrl(Tenant $tenant): string
    {
        $appUrl = parse_url(config('app.url'));
        $port = isset($appUrl['port']) ? ':' . $appUrl['port'] : '';
        $scheme = $appUrl['scheme'] ?? 'http';
        $centralHost = $appUrl['host'] ?? 'localhost';

        return "{$scheme}://{$tenant->subdomain}.{$centralHost}{$port}";
    }

    private function maskEmail(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);
        $visible = substr($local, 0, 2);
        return $visible . str_repeat('*', max(1, strlen($local) - 2)) . '@' . $domain;
    }
}
