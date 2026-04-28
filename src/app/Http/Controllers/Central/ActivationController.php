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
