<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionTenantJob;
use App\Models\ActivationCode;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        // Two valid entry states:
        //   awaiting_activation → self-service signup, DB not yet provisioned. We
        //                         provision below, then create the admin user.
        //   pending_activation  → super-admin manually onboarded; DB already exists.
        //                         Skip straight to admin user creation.
        if (! in_array($tenant->status, ['awaiting_activation', 'pending_activation'], true)) {
            return back()
                ->withInput($request->only('code'))
                ->withErrors([
                    'code' => "This school's status is '{$tenant->status}' — activation is no longer accepted.",
                ]);
        }

        // If this is a deferred self-service signup, do the heavy work now
        // (creates the per-tenant DB, runs ~80 migrations, seeds template
        // data). On failure, leave the tenant in `awaiting_activation` so
        // the user can click the email link again to retry without losing
        // their code.
        if ($tenant->status === 'awaiting_activation') {
            // Lock in the final database name now that we know the tenant id.
            // The signup flow stored a placeholder; replace it before
            // ProvisionTenantJob reads it.
            if (! str_starts_with((string) $tenant->getAttribute('database'), 'tenant_' . $tenant->id)) {
                $tenant->update(['database' => 'tenant_' . $tenant->id]);
                $tenant->refresh();
            }

            $tenant->update(['status' => 'provisioning']);

            try {
                (new ProvisionTenantJob($tenant))->handle();
            } catch (\Throwable $e) {
                // Roll back the status flag so a retry from the email link
                // hits the same code path cleanly. Don't delete the tenant
                // row — the activation code is still valid and we want the
                // user to be able to retry without re-paying.
                Log::error('Deferred provisioning failed at activation', [
                    'tenant_id' => $tenant->id,
                    'error'     => $e->getMessage(),
                ]);
                $tenant->update(['status' => 'awaiting_activation']);
                return back()
                    ->withInput($request->only('code'))
                    ->withErrors([
                        'code' => 'We hit a snag setting up your school. Please try the activation link again in a minute. If it keeps failing, contact support.',
                    ]);
            }
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
            'loginUrl'    => tenant_url($tenant->subdomain, '/login'),
        ]);
    }
}
