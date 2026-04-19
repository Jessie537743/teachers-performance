<?php

declare(strict_types=1);

use Stancl\Tenancy\Database\Models\Domain;
use Stancl\Tenancy\Database\Models\Tenant as DefaultTenant;

return [
    'tenant_model'          => \App\Models\Tenant::class,
    'id_generator'          => null, // we use auto-incrementing bigint ids

    'domain_model'          => Domain::class, // unused — we resolve by subdomain column

    /*
     * Hostnames hit on the central domain are NEVER treated as tenants.
     * Add 'localhost' (Docker dev), 'admin.localhost' (super-admin UI),
     * plus your production domain.
     */
    'central_domains' => array_filter(
        array_map('trim', explode(',', env('TENANCY_CENTRAL_DOMAINS', 'localhost,admin.localhost')))
    ),

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        // CacheTenancyBootstrapper omitted — requires a tag-supporting cache
        // driver (redis/memcached/array) and our CACHE_STORE is `file`.
        // We don't make heavy use of tenant-scoped caching, so DB-only
        // isolation is sufficient for the capstone.
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => 'central',
        // Stancl clones this connection's settings (host/credentials) and
        // swaps in the tenant's database name on initialize. Without it,
        // DB::connection() does NOT actually swap to the tenant DB.
        'template_tenant_connection' => 'mysql',
        'prefix'   => 'tenant_',
        'suffix'   => '',
        'managers' => [
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks'       => ['local', 'public'],
        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],

    'queue' => [
        // tenancy bootstrap re-runs on each queued job
    ],

    'features' => [
        // we register custom features only when needed
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path'  => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],

    'seeder_parameters' => [
        // populated in Phase 2 (TenantTemplateSeeder)
    ],
];
