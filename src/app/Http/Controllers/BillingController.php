<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\BillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function cancel(BillingService $billing): RedirectResponse
    {
        /** @var Tenant $tenant */
        $tenant = tenant();

        if (! in_array($tenant->subscription_status, ['active', 'grace'], true)) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        $billing->cancel($tenant);

        return back()->with('status', "Subscription canceled. Access continues until {$tenant->fresh()->current_period_end?->toDayDateTimeString()}.");
    }

    /**
     * Self-serve checkout. Shows a simulated payment summary for the chosen
     * plan + cycle. Enterprise has no monthly price (custom-quoted) so it is
     * rejected here and routed back to the upgrade page.
     */
    public function checkout(Request $request): View|RedirectResponse
    {
        $planSlug = $request->query('plan');
        $cycle    = $request->query('cycle', 'monthly');

        $plan = config("plans.{$planSlug}");
        if (! $plan || $planSlug === plan()->planSlug()) {
            return redirect()->route('plan.upgrade')->with('error', 'Invalid plan selection.');
        }

        if (BillingService::priceCents($planSlug, $cycle) === null) {
            return redirect()->route('plan.upgrade')->with('error', "{$plan['name']} is custom-priced — please contact sales.");
        }

        return view('billing.checkout', [
            'plan'        => $plan,
            'planSlug'    => $planSlug,
            'cycle'       => $cycle,
            'priceCents'  => BillingService::priceCents($planSlug, $cycle),
            'currentPlan' => plan()->plan(),
        ]);
    }

    /**
     * Confirm the simulated payment, switch the tenant's plan, record a
     * Subscription row, and bounce back to the dashboard.
     */
    public function confirmCheckout(Request $request, BillingService $billing): RedirectResponse
    {
        $data = $request->validate([
            'plan'  => ['required', 'string'],
            'cycle' => ['required', 'in:monthly,yearly'],
        ]);

        $plan = config("plans.{$data['plan']}");
        if (! $plan || BillingService::priceCents($data['plan'], $data['cycle']) === null) {
            return redirect()->route('plan.upgrade')->with('error', 'Invalid plan or cycle.');
        }

        /** @var Tenant $tenant */
        $tenant = tenant();
        $tenant->update(['plan' => $data['plan']]);
        $billing->startSubscription($tenant->refresh(), $data['cycle']);

        return redirect()->route('dashboard')
            ->with('status', "You're now on the {$plan['name']} plan. Welcome aboard!");
    }
}
