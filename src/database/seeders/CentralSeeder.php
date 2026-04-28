<?php

namespace Database\Seeders;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentralSeeder extends Seeder
{
    public function run(): void
    {
        // First super-admin (you, the platform operator).
        SuperAdmin::firstOrCreate(
            ['email' => 'super@platform.test'],
            [
                'name'      => 'Platform Super Admin',
                'password'  => Hash::make('super123'),
                'is_active' => true,
            ],
        );

        // One-time backfill: register the legacy JCD school against the existing
        // teachers_performance DB. Only runs on a fresh install (no tenants yet);
        // otherwise it's a no-op so re-running the seeder is always safe.
        if (Tenant::count() === 0) {
            $jcd = Tenant::create([
                'name'      => 'JCD',
                'subdomain' => 'jcd',
                'database'  => 'teachers_performance',
                'status'    => 'active',
            ]);

            $jcd->domains()->firstOrCreate(['domain' => 'jcd']);
        }
    }
}
