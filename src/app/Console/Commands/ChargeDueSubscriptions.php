<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BillingService;
use Illuminate\Console\Command;

/**
 * Daily recurring charge cycle. Finds tenants whose `next_charge_at` is due
 * and either of: subscription_status=active OR =grace (retry).
 *
 * Charges are simulated by BillingService (replace with Stripe when ready).
 */
class ChargeDueSubscriptions extends Command
{
    protected $signature = 'subscriptions:charge-due
                            {--tenant= : Charge a single tenant by id (skips date check)}
                            {--dry-run : Don\'t persist changes, just report what would happen}';

    protected $description = 'Charge tenants whose next_charge_at has elapsed';

    public function handle(BillingService $billing): int
    {
        $query = Tenant::query()
            ->whereIn('subscription_status', ['active', 'grace'])
            ->whereNotNull('next_charge_at');

        if ($id = $this->option('tenant')) {
            $query->where('id', $id);
        } else {
            $query->where('next_charge_at', '<=', now());
        }

        $due = $query->get();

        if ($due->isEmpty()) {
            $this->info('No subscriptions due.');
            return self::SUCCESS;
        }

        $this->info("Processing {$due->count()} due subscription(s)" . ($this->option('dry-run') ? ' (DRY RUN)' : '') . '...');

        $charged = 0;
        $failed  = 0;

        foreach ($due as $tenant) {
            if ($this->option('dry-run')) {
                $this->line("  - [{$tenant->id}] {$tenant->name} would be charged for {$tenant->billing_cycle}");
                continue;
            }

            $sub = $billing->chargeNextPeriod($tenant);
            $tenant->refresh();

            if ($sub->status === 'paid') {
                $charged++;
                $this->line("  ✓ [{$tenant->id}] {$tenant->name} — charged \${$sub->formatted_amount}, next: {$tenant->next_charge_at?->toDayDateTimeString()}");
            } else {
                $failed++;
                $this->warn("  ✗ [{$tenant->id}] {$tenant->name} — {$sub->failure_reason}, retry at {$tenant->next_charge_at?->toDayDateTimeString()}");
            }
        }

        if (! $this->option('dry-run')) {
            $this->newLine();
            $this->info("Charged: {$charged}   Failed: {$failed}");
        }

        return self::SUCCESS;
    }
}
