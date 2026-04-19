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

class ProvisionTenantJob
{
    use Dispatchable, Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $adminName,
        public string $adminEmail,
        public string $adminTempPassword,
    ) {}

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
            $this->createFirstAdmin();

            $this->tenant->update(['status' => 'active']);

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

        // Connect to MySQL on the central host but without selecting a
        // database, so we can issue CREATE DATABASE.
        $centralConfig = config('database.connections.central');
        Config::set('database.connections._tenant_provisioner', array_merge($centralConfig, ['database' => null]));
        DB::purge('_tenant_provisioner');

        DB::connection('_tenant_provisioner')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$databaseName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // The MYSQL_DATABASE/MYSQL_USER docker pair only auto-grants the
        // initial DB to the user. Subsequent DBs need an explicit GRANT or
        // the tenant connection (running as tp_user) cannot read them.
        $tenantUser = config('database.connections.mysql.username');
        DB::connection('_tenant_provisioner')->statement(
            "GRANT ALL PRIVILEGES ON `{$databaseName}`.* TO '{$tenantUser}'@'%'"
        );
        DB::connection('_tenant_provisioner')->statement('FLUSH PRIVILEGES');
    }

    protected function runTenantMigrations(): void
    {
        // Stancl's tenants:migrate command initializes tenancy, runs the
        // migrations under config('tenancy.migration_parameters'), and
        // tears down. Pass the tenant id as a string (stancl expects strings).
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

    protected function createFirstAdmin(): void
    {
        tenancy()->initialize($this->tenant);

        try {
            // While tenancy is initialized, the default `mysql` connection
            // points at the tenant DB. Use the User model so password
            // hashing, casts, and role serialization stay consistent with
            // the rest of the app.
            \App\Models\User::create([
                'name'                 => $this->adminName,
                'email'                => $this->adminEmail,
                'password'             => $this->adminTempPassword, // 'hashed' cast
                'roles'                => ['admin'],                // 'array' cast
                'is_active'            => true,
                'must_change_password' => true,
            ]);
        } finally {
            tenancy()->end();
        }
    }
}
