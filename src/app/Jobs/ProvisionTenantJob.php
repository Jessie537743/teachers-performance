<?php

namespace App\Jobs;

use App\Models\Tenant;
use App\Models\TenantProvisioningJob;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Provisions a tenant DB. Caller decides what status to set on the tenant
 * after success — typically `pending_activation` for fresh wizard creations
 * (the school admin will set their password via the activation flow).
 */
class ProvisionTenantJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(public Tenant $tenant) {}

    public function handle(): void
    {
        $audit = TenantProvisioningJob::create([
            'tenant_id'  => $this->tenant->id,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            $this->createDatabase();
            $this->runTenantMigrations();
            $this->seedTemplate();

            $audit->update([
                'status'      => 'succeeded',
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Tenant provisioning failed', [
                'tenant_id' => $this->tenant->id,
                'error'     => $e->getMessage(),
            ]);

            $this->tenant->update(['status' => 'failed']);

            $audit->update([
                'status'      => 'failed',
                'error'       => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    protected function createDatabase(): void
    {
        $databaseName = $this->tenant->getAttribute('database');

        $centralConfig = config('database.connections.central');
        Config::set('database.connections._tenant_provisioner', array_merge($centralConfig, ['database' => null]));
        DB::purge('_tenant_provisioner');

        DB::connection('_tenant_provisioner')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        $tenantUser = config('database.connections.mysql.username');
        DB::connection('_tenant_provisioner')->statement(
            "GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$tenantUser}'@'%'"
        );
        DB::connection('_tenant_provisioner')->statement('FLUSH PRIVILEGES');
    }

    protected function runTenantMigrations(): void
    {
        Artisan::call('tenants:migrate', [
            '--tenants' => [(string) $this->tenant->id],
            '--force'   => true,
        ]);
    }

    protected function seedTemplate(): void
    {
        Artisan::call('tenants:seed', [
            '--tenants' => [(string) $this->tenant->id],
            '--class'   => 'Database\\Seeders\\TenantTemplateSeeder',
            '--force'   => true,
        ]);
    }
}
