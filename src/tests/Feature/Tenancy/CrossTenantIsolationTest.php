<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CrossTenantIsolationTest extends TestCase
{
    public function test_data_written_in_tenant_a_is_invisible_from_tenant_b(): void
    {
        // Use the existing JCD tenant as A.
        $tenantA = Tenant::where('subdomain', 'jcd')->firstOrFail();

        // Provision a temporary tenant B for this test. We don't run the full
        // ProvisionTenantJob (slow, also runs the TenantTemplateSeeder which
        // does TRUNCATEs); instead we create a Tenant row pointing at a hand-
        // created small DB and run only the migrations needed to have a
        // `users` table to compare.
        $tenantBdb = 'isolation_test_' . substr(md5(uniqid()), 0, 6);
        DB::connection('central')->statement(
            "CREATE DATABASE `{$tenantBdb}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        try {
            $tenantB = Tenant::create([
                'name'      => 'Isolation Test School',
                'subdomain' => str_replace('_', '-', $tenantBdb),
                'database'  => $tenantBdb,
                'status'    => 'active',
            ]);
            $tenantB->domains()->create(['domain' => str_replace('_', '-', $tenantBdb)]);

            // Run the tenant migrations against tenant B (creates `users` etc.).
            \Artisan::call('tenants:migrate', [
                '--tenants' => [(string) $tenantB->id],
                '--force'   => true,
            ]);

            // Sanity check: tenant A has many users, tenant B has none.
            tenancy()->initialize($tenantA);
            $countA = DB::table('users')->count();
            tenancy()->end();

            tenancy()->initialize($tenantB);
            $countB = DB::table('users')->count();
            tenancy()->end();

            $this->assertGreaterThan(0, $countA, 'JCD should have users (Phase 1 baseline).');
            $this->assertSame(0, $countB, 'Fresh tenant B should have zero users.');

            // The actual isolation assertion: insert a unique marker in B's users,
            // then switch to A and verify it's NOT visible.
            tenancy()->initialize($tenantB);
            $markerEmail = 'leakage-marker-' . uniqid() . '@isolation.test';
            DB::table('users')->insert([
                'name'                 => 'Marker User',
                'email'                => $markerEmail,
                'password'             => bcrypt('x'),
                'is_active'            => true,
                'must_change_password' => false,
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
            $countBafter = DB::table('users')->where('email', $markerEmail)->count();
            tenancy()->end();

            $this->assertSame(1, $countBafter, 'Marker should be visible in tenant B.');

            tenancy()->initialize($tenantA);
            $countAhasMarker = DB::table('users')->where('email', $markerEmail)->count();
            tenancy()->end();

            $this->assertSame(
                0,
                $countAhasMarker,
                "Tenant A leaked: tenant B's marker user is visible from tenant A's context.",
            );
        } finally {
            // Cleanup: drop the temp DB and the central rows.
            try {
                if (isset($tenantB)) {
                    $tenantB->domains()->delete();
                    $tenantB->delete();
                }
                DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$tenantBdb}`");
            } catch (\Throwable $e) {
                // best-effort cleanup
            }
        }
    }
}
