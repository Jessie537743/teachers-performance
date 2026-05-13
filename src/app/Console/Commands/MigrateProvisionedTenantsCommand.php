<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Thin wrapper around stancl's `tenants:migrate` that skips tenants whose
 * databases haven't been provisioned yet (status = awaiting_activation).
 *
 * Why this exists: with the deferred-provisioning flow, a tenant row is
 * created at /subscribe time but its per-tenant database is NOT created
 * until the user redeems their activation code. Running stancl's plain
 * `tenants:migrate` against the entire tenants table would try to connect
 * to those nonexistent databases and crash. This command resolves the
 * eligible tenant IDs first and passes them explicitly via `--tenants=`.
 *
 * The Docker entrypoint calls this instead of the raw stancl command.
 */
class MigrateProvisionedTenantsCommand extends Command
{
    protected $signature = 'tenants:migrate-provisioned {--force : Run without confirmation in production}';

    protected $description = 'Run tenants:migrate but only for tenants whose databases have actually been provisioned.';

    public function handle(): int
    {
        $ids = Tenant::query()
            ->whereNotIn('status', ['awaiting_activation'])
            ->pluck('id')
            ->all();

        if (empty($ids)) {
            $this->info('[tenants:migrate-provisioned] No provisioned tenants — nothing to migrate.');
            return self::SUCCESS;
        }

        $this->info('[tenants:migrate-provisioned] Migrating ' . count($ids) . ' provisioned tenants...');

        $exit = Artisan::call('tenants:migrate', [
            '--tenants' => array_map('strval', $ids),
            '--force'   => (bool) $this->option('force'),
        ]);

        // Forward the underlying command's output so deploy logs stay readable.
        $this->line(Artisan::output());

        return $exit;
    }
}
