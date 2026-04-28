<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Jobs\ProvisionTenantJob;
use App\Models\Tenant;
use App\Models\User;

$subdomain = 'demo';
$adminEmail = 'admin@demo.localhost';
$adminPassword = 'password';

$existing = Tenant::where('subdomain', $subdomain)->first();
if ($existing) {
    echo "Tenant '{$subdomain}' already exists (id={$existing->id}, status={$existing->status}).\n";
    exit(0);
}

$tenant = Tenant::create([
    'name'      => 'Demo School',
    'subdomain' => $subdomain,
    'database'  => 'tenant_pending',
    'status'    => 'provisioning',
    'plan'      => 'pro',
]);

$tenant->domains()->create(['domain' => $subdomain]);
$tenant->update(['database' => 'tenant_' . $tenant->id]);
$tenant->refresh();

echo "Tenant row created (id={$tenant->id}, db={$tenant->database}). Provisioning...\n";

(new ProvisionTenantJob($tenant))->handle();

echo "Provisioning succeeded. Creating admin user inside tenant DB...\n";

tenancy()->initialize($tenant);
try {
    User::create([
        'name'                 => 'Demo Admin',
        'email'                => $adminEmail,
        'password'             => $adminPassword,
        'roles'                => ['admin'],
        'is_active'            => true,
        'must_change_password' => false,
    ]);
} finally {
    tenancy()->end();
}

$tenant->update(['status' => 'active']);

echo "Done.\n";
echo "  Tenant:   {$tenant->name} (id={$tenant->id}, plan={$tenant->plan}, status=active)\n";
echo "  Domain:   {$subdomain}.localhost:8081\n";
echo "  Login:    http://{$subdomain}.localhost:8081/login\n";
echo "  Email:    {$adminEmail}\n";
echo "  Password: {$adminPassword}\n";
