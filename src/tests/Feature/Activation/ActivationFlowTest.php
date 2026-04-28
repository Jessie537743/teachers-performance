<?php

namespace Tests\Feature\Activation;

use App\Models\ActivationCode;
use App\Models\Tenant;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivationFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    /**
     * Helper to create a tenant + DB + activation code in one call.
     * Tests cleanup the tenant_DB themselves in their finally blocks.
     */
    private function provisionTestTenant(string $statusOverride = 'pending_activation', ?string $codeStatus = 'unredeemed'): array
    {
        $subdomain = 'aft' . substr(md5(uniqid()), 0, 8);
        $database = 'tenant_aft_' . substr(md5(uniqid()), 0, 8);

        DB::connection('central')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenant = Tenant::create([
            'name'      => 'Activation Test ' . $subdomain,
            'subdomain' => $subdomain,
            'database'  => $database,
            'status'    => $statusOverride,
            'plan'      => 'free',
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        \Artisan::call('tenants:migrate', ['--tenants' => [(string) $tenant->id], '--force' => true]);

        $code = ActivationCode::create([
            'tenant_id'            => $tenant->id,
            'code'                 => ActivationCode::generate(),
            'plan'                 => 'free',
            'intended_admin_name'  => 'Activation Tester',
            'intended_admin_email' => 'tester@' . $subdomain . '.test',
            'status'               => $codeStatus,
            'expires_at'           => $codeStatus === 'expired' ? now()->subDay() : now()->addDays(30),
            'redeemed_at'          => $codeStatus === 'redeemed' ? now() : null,
            'revoked_at'           => $codeStatus === 'revoked' ? now() : null,
        ]);

        return [$tenant, $code, $database];
    }

    private function cleanup(Tenant $tenant, string $database): void
    {
        try {
            $tenant->activationCodes()->delete();
            $tenant->domains()->delete();
            $tenant->delete();
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$database}`");
        } catch (\Throwable $e) {
            // best-effort
        }
    }

    public function test_get_activate_with_valid_code_pre_fills_form(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant();
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('Activate your school');
            $response->assertSee($tenant->name);
            $response->assertSee($code->intended_admin_email);
            $response->assertSee($code->code);
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_redeemed_code_shows_invalid(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('active', 'redeemed');
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('already used');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_revoked_code_shows_invalid(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('pending_activation', 'revoked');
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('was revoked');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_expired_code_shows_invalid(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('pending_activation', 'unredeemed');
        // Force-expire by setting expires_at in the past
        $code->update(['expires_at' => now()->subDay()]);
        try {
            $response = $this->get('http://localhost/activate?code=' . $code->code);

            $response->assertOk();
            $response->assertSee('expired');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_get_activate_with_missing_code_shows_blank_form(): void
    {
        $response = $this->get('http://localhost/activate');

        $response->assertOk();
        $response->assertSee('Activate your school');
    }

    public function test_post_activate_redeems_code_creates_user_activates_tenant(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant();
        try {
            $response = $this->post('http://localhost/activate', [
                'code'                  => $code->code,
                'password'              => 'mySecure!Pass1',
                'password_confirmation' => 'mySecure!Pass1',
            ]);

            $response->assertOk();
            $response->assertSee('is ready');

            $tenant->refresh();
            $this->assertSame('active', $tenant->status);

            $code->refresh();
            $this->assertSame('redeemed', $code->status);
            $this->assertNotNull($code->redeemed_at);

            // User row was created in the tenant DB
            $userCount = DB::connection('mysql')->getPdo()
                ->query("SELECT COUNT(*) FROM `{$database}`.users WHERE email='{$code->intended_admin_email}'")->fetchColumn();
            $this->assertSame(1, (int) $userCount);
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_post_activate_with_mismatched_password_confirmation_rejects(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant();
        try {
            $response = $this->post('http://localhost/activate', [
                'code'                  => $code->code,
                'password'              => 'mySecure!Pass1',
                'password_confirmation' => 'somethingElse',
            ]);

            $response->assertSessionHasErrors('password');

            $tenant->refresh();
            $this->assertSame('pending_activation', $tenant->status);
        } finally {
            $this->cleanup($tenant, $database);
        }
    }

    public function test_post_activate_with_already_redeemed_code_rejects(): void
    {
        [$tenant, $code, $database] = $this->provisionTestTenant('active', 'redeemed');
        try {
            $response = $this->post('http://localhost/activate', [
                'code'                  => $code->code,
                'password'              => 'mySecure!Pass1',
                'password_confirmation' => 'mySecure!Pass1',
            ]);

            $response->assertSessionHasErrors('code');
        } finally {
            $this->cleanup($tenant, $database);
        }
    }
}
