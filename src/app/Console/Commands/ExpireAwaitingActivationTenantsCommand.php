<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deletes tenant rows that signed up via /subscribe but never redeemed their
 * activation code within the grace period. Since these tenants never had a
 * per-tenant database provisioned (status = awaiting_activation), cleanup
 * is just a row delete plus cascading the related activation_codes,
 * subscriptions, domains, and provisioning_jobs rows — no DROP DATABASE
 * needed.
 *
 * Scheduled weekly via app(\Illuminate\Console\Scheduling\Schedule::class)
 * in routes/console.php; can also be run manually:
 *
 *   php artisan tenants:expire-awaiting --days=7
 */
class ExpireAwaitingActivationTenantsCommand extends Command
{
    protected $signature = 'tenants:expire-awaiting
        {--days=7 : Delete awaiting_activation tenants older than this many days}
        {--dry-run : Show what would be deleted without making changes}';

    protected $description = 'Delete unredeemed awaiting_activation tenant rows older than the configured grace period.';

    public function handle(): int
    {
        $days   = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);
        $dryRun = (bool) $this->option('dry-run');

        $victims = Tenant::query()
            ->where('status', 'awaiting_activation')
            ->where('created_at', '<', $cutoff)
            ->whereDoesntHave('activationCodes', fn ($q) => $q->where('status', 'redeemed'))
            ->get();

        $count = $victims->count();

        if ($count === 0) {
            $this->info("[tenants:expire-awaiting] Nothing to expire (cutoff: {$days} days).");
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[dry-run] ' : '') . "Found {$count} tenants in awaiting_activation older than {$days} days.");

        if ($dryRun) {
            foreach ($victims as $tenant) {
                $this->line("  - id={$tenant->id} subdomain={$tenant->subdomain} created={$tenant->created_at}");
            }
            return self::SUCCESS;
        }

        $removed = 0;
        DB::beginTransaction();
        try {
            foreach ($victims as $tenant) {
                $tenant->provisioningJobs()->delete();
                $tenant->activationCodes()->delete();
                $tenant->subscriptions()->delete();
                $tenant->domains()->delete();
                $tenant->delete();
                $removed++;
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Rollback. ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Removed {$removed} expired awaiting_activation tenants.");
        return self::SUCCESS;
    }
}
