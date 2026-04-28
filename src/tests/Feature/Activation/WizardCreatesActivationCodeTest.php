<?php

namespace Tests\Feature\Activation;

use App\Models\ActivationCode;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class WizardCreatesActivationCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_wizard_creates_pending_tenant_with_unredeemed_code_and_no_user(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();
        $subdomain = 'wztest' . substr(md5(uniqid()), 0, 6);

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Wizard Test School',
            'subdomain'   => $subdomain,
            'admin_name'  => 'Wizard Admin',
            'admin_email' => 'wadmin@wztest.test',
            'plan'        => 'pro',
        ]);

        $response->assertOk();
        $response->assertSee('is provisioned');

        $tenant = Tenant::where('subdomain', $subdomain)->firstOrFail();
        $this->assertSame('pending_activation', $tenant->status);
        $this->assertSame('pro', $tenant->plan);

        $code = $tenant->activationCodes()->latest()->first();
        $this->assertNotNull($code);
        $this->assertSame('unredeemed', $code->status);
        $this->assertSame('pro', $code->plan);
        $this->assertSame('wadmin@wztest.test', $code->intended_admin_email);
        $this->assertMatchesRegularExpression('/^[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $code->code);

        // Critical: NO user should exist yet — the activation flow creates them
        $tenantDb = $tenant->getAttribute('database');
        $userCount = DB::connection('mysql')->getPdo()
            ->query("SELECT COUNT(*) FROM `{$tenantDb}`.users")->fetchColumn();
        $this->assertSame(0, (int) $userCount, 'Wizard must NOT create a user — that is the activation flow\'s job.');
    }

    public function test_wizard_rejects_invalid_plan_slug(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->actingAs($admin, 'super_admin')->post('http://admin.localhost/tenants', [
            'name'        => 'Bad Plan School',
            'subdomain'   => 'badplan' . substr(md5(uniqid()), 0, 4),
            'admin_name'  => 'X',
            'admin_email' => 'x@y.test',
            'plan'        => 'platinum',  // not in config/plans.php
        ]);

        $response->assertSessionHasErrors('plan');
    }
}
