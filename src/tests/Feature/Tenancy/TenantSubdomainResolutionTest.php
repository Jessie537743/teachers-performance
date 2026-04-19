<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use Tests\TestCase;

class TenantSubdomainResolutionTest extends TestCase
{
    public function test_jcd_tenant_row_resolves_to_the_existing_teachers_performance_database(): void
    {
        $tenant = Tenant::where('subdomain', 'jcd')->firstOrFail();

        $this->assertSame('teachers_performance', $tenant->getAttribute('database'));
        $this->assertSame('active', $tenant->status);
    }

    public function test_jcd_tenant_has_a_matching_domains_row_for_subdomain_resolution(): void
    {
        $tenant = Tenant::where('subdomain', 'jcd')->firstOrFail();
        $domains = $tenant->domains()->pluck('domain')->all();

        $this->assertContains('jcd', $domains, 'JCD tenant must have a `jcd` row in the central `domains` table — InitializeTenancyBySubdomain looks it up there.');
    }

    public function test_central_landing_is_accessible_on_central_domain(): void
    {
        $response = $this->get('http://localhost/');

        $response->assertOk();
        $response->assertSee('Multi-Tenant Platform');
    }

    public function test_admin_placeholder_is_accessible_on_admin_subdomain(): void
    {
        $response = $this->get('http://admin.localhost/');

        $response->assertOk();
        $response->assertSee('Super-admin dashboard');
    }

    public function test_jcd_subdomain_serves_the_login_page(): void
    {
        $response = $this->get('http://jcd.localhost/login');

        $response->assertOk();
    }

    public function test_tenant_routes_are_blocked_on_central_domain_with_404_not_500(): void
    {
        $response = $this->get('http://localhost/login');

        $response->assertStatus(404);
    }
}
