<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProvisionTenantTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    /**
     * NOTE: These tests create real tenant DBs (tenant_<id>) that persist
     * after the test run. Inspect them or drop manually with:
     *   docker exec tp-db mysql -u root -psecret -e "DROP DATABASE tenant_<id>"
     * Then delete the central row + domain row to keep the central DB tidy.
     */
    public function test_super_admin_can_provision_a_new_school(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $subdomain = 'test' . substr(md5(uniqid()), 0, 6);

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Test School ' . uniqid(),
            'subdomain'   => $subdomain,
            'admin_name'  => 'Test Admin',
            'admin_email' => 'admin@test.test',
            'plan'        => 'free',
        ]);

        $response->assertOk();
        $response->assertSee('is provisioned');
        $response->assertSee('admin@test.test');

        // Wizard now leaves the tenant in pending_activation; the activation
        // flow (separately tested) creates the user + transitions to active.
        $newTenant = Tenant::where('subdomain', $subdomain)->firstOrFail();
        $this->assertSame('pending_activation', $newTenant->status);
        $this->assertSame('tenant_' . $newTenant->id, $newTenant->getAttribute('database'));

        // Verify the tenant DB has the seeded template data and no users
        $tenantDb = $newTenant->getAttribute('database');
        $criteriaCount = DB::connection('mysql')->getPdo()
            ->query("SELECT COUNT(*) FROM `{$tenantDb}`.criteria")->fetchColumn();
        $userCount = DB::connection('mysql')->getPdo()
            ->query("SELECT COUNT(*) FROM `{$tenantDb}`.users")->fetchColumn();

        $this->assertGreaterThan(0, $criteriaCount, 'Tenant DB should have seeded criteria');
        $this->assertSame(0, (int) $userCount, 'Wizard must NOT create a user — that is the activation flow\'s job.');

        // An unredeemed activation code should exist for the new tenant
        $code = $newTenant->activationCodes()->latest()->first();
        $this->assertNotNull($code);
        $this->assertSame('unredeemed', $code->status);
        $this->assertSame('free', $code->plan);
    }

    public function test_subdomain_validation_rejects_reserved_words(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Bad School',
            'subdomain'   => 'admin', // reserved
            'admin_name'  => 'X',
            'admin_email' => 'x@y.test',
            'plan'        => 'free',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }

    public function test_subdomain_validation_rejects_already_taken(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Duplicate School',
            'subdomain'   => 'jcd', // already exists
            'admin_name'  => 'X',
            'admin_email' => 'x@y.test',
            'plan'        => 'free',
        ]);

        $response->assertSessionHasErrors('subdomain');
    }
}
