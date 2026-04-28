<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BillingController extends Controller
{
    public function show(): View
    {
        /** @var Tenant $tenant */
        $tenant = tenant();

        // Subscription rows live in the central DB and aren't tenant-scoped via Eloquent;
        // the relation already enforces the tenant_id filter.
        $tenant->load(['subscriptions' => fn ($q) => $q->orderByDesc('id')->limit(24)]);

        return view('billing.show', [
            'tenant'        => $tenant,
            'subscriptions' => $tenant->subscriptions,
            'plan'          => config('plans.' . $tenant->plan),
            'planSlug'      => $tenant->plan,
        ]);
    }

    public function cancel(\App\Services\BillingService $billing): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = tenant();

        if (! in_array($tenant->subscription_status, ['active', 'grace'], true)) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        $billing->cancel($tenant);

        return back()->with('status', "Subscription canceled. Access continues until {$tenant->fresh()->current_period_end?->toDayDateTimeString()}.");
    }
}
