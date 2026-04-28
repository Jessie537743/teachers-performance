<?php

namespace App\Services;

use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Handles subscription lifecycle: starting a new subscription, charging the
 * next period, advancing dates, recording invoices.
 *
 * Charges are simulated for the capstone — every attempt currently succeeds.
 * The `simulateCharge()` method is the seam where real Stripe / PayPal logic
 * would slot in later (return success/failure + reference id from gateway).
 */
class BillingService
{
    /**
     * Cycle → Carbon advancement function.
     */
    public static function advance(Carbon $from, string $cycle): Carbon
    {
        return match ($cycle) {
            'monthly' => $from->copy()->addMonth(),
            'yearly'  => $from->copy()->addYear(),
            default   => $from->copy(),
        };
    }

    /**
     * Price for a plan + cycle in cents (or null if not priced — e.g. enterprise).
     */
    public static function priceCents(string $planSlug, string $cycle): ?int
    {
        $price = config("plans.{$planSlug}.prices.{$cycle}");
        if ($price === null) {
            return null;
        }
        return (int) round((float) $price * 100);
    }

    /**
     * Start a tenant's subscription at signup. Records the first paid period
     * and schedules the next charge. Free plans skip charge simulation.
     */
    public function startSubscription(Tenant $tenant, string $cycle): Subscription
    {
        $now         = now();
        $periodStart = $now->copy();
        $periodEnd   = self::advance($now, $cycle);
        $amountCents = self::priceCents($tenant->plan, $cycle) ?? 0;
        $isFree      = $amountCents === 0;

        $subscription = Subscription::create([
            'tenant_id'      => $tenant->id,
            'plan'           => $tenant->plan,
            'billing_cycle'  => $cycle,
            'amount_cents'   => $amountCents,
            'currency'       => 'USD',
            'period_start'   => $periodStart,
            'period_end'     => $periodEnd,
            'status'         => 'paid',
            'paid_at'        => $now,
            'reference'      => $isFree ? 'free' : 'sim_' . Str::random(16),
        ]);

        $tenant->update([
            'billing_cycle'        => $cycle,
            'subscription_status'  => 'active',
            'current_period_start' => $periodStart,
            'current_period_end'   => $periodEnd,
            'next_charge_at'       => $isFree ? null : $periodEnd,
            'last_charge_at'       => $isFree ? null : $now,
        ]);

        return $subscription;
    }

    /**
     * Charge a tenant for the next period. Returns the new Subscription row
     * (status=paid on success, status=failed on simulated failure).
     */
    public function chargeNextPeriod(Tenant $tenant): Subscription
    {
        $cycle       = $tenant->billing_cycle ?? 'monthly';
        $amountCents = self::priceCents($tenant->plan, $cycle) ?? 0;
        $now         = now();

        $periodStart = $tenant->current_period_end?->copy() ?? $now->copy();
        $periodEnd   = self::advance($periodStart, $cycle);

        // Simulated gateway call — returns ['success' => bool, 'reference' => ?string, 'reason' => ?string]
        $result = $this->simulateCharge($tenant, $amountCents);

        if ($result['success']) {
            $subscription = Subscription::create([
                'tenant_id'     => $tenant->id,
                'plan'          => $tenant->plan,
                'billing_cycle' => $cycle,
                'amount_cents'  => $amountCents,
                'currency'      => 'USD',
                'period_start'  => $periodStart,
                'period_end'    => $periodEnd,
                'status'        => 'paid',
                'paid_at'       => $now,
                'reference'     => $result['reference'],
            ]);

            $tenant->update([
                'subscription_status'  => 'active',
                'current_period_start' => $periodStart,
                'current_period_end'   => $periodEnd,
                'next_charge_at'       => $periodEnd,
                'last_charge_at'       => $now,
            ]);

            return $subscription;
        }

        // Failure path — record + put tenant in grace period (3 days) before suspending
        $subscription = Subscription::create([
            'tenant_id'      => $tenant->id,
            'plan'           => $tenant->plan,
            'billing_cycle'  => $cycle,
            'amount_cents'   => $amountCents,
            'currency'       => 'USD',
            'period_start'   => $periodStart,
            'period_end'     => $periodEnd,
            'status'         => 'failed',
            'failure_reason' => $result['reason'] ?? 'Simulated decline',
        ]);

        $tenant->update([
            'subscription_status' => 'grace',
            'next_charge_at'      => $now->copy()->addDays(3),  // retry in 3 days
        ]);

        return $subscription;
    }

    /**
     * Cancel a subscription. Tenant keeps access until current_period_end.
     */
    public function cancel(Tenant $tenant): void
    {
        $tenant->update([
            'subscription_status' => 'canceled',
            'next_charge_at'      => null,
        ]);
    }

    /**
     * Stub for the payment gateway. Replace with Stripe/PayPal API call.
     * For the capstone simulation, every charge succeeds unless the tenant
     * was created with a marker that triggers failure (none defined yet —
     * all charges currently succeed).
     */
    private function simulateCharge(Tenant $tenant, int $amountCents): array
    {
        return [
            'success'   => true,
            'reference' => 'sim_' . Str::random(16),
            'reason'    => null,
        ];
    }
}
