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

        // Existing JCD school registered as tenant id 1.
        // Points at the existing teachers_performance DB — no data movement.
        $jcd = Tenant::firstOrCreate(
            ['subdomain' => 'jcd'],
            [
                'id'       => 1,
                'name'     => 'JCD',
                'database' => 'teachers_performance',
                'status'   => 'active',
            ],
        );

        // Stancl's InitializeTenancyBySubdomain middleware looks up tenants
        // through the `domains` table. Mirror the subdomain there.
        $jcd->domains()->firstOrCreate(['domain' => 'jcd']);
    }
}
