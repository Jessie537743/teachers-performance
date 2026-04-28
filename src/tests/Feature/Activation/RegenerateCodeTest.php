<?php

namespace Tests\Feature\Activation;

use App\Models\ActivationCode;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RegenerateCodeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_regenerate_revokes_current_code_and_creates_a_new_one(): void
    {
        $subdomain = 'regen' . substr(md5(uniqid()), 0, 6);
        $database = 'tenant_regen_' . substr(md5(uniqid()), 0, 6);
        DB::connection('central')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenant = Tenant::create([
            'name' => 'Regenerate Test',
            'subdomain' => $subdomain,
            'database' => $database,
            'status' => 'pending_activation',
            'plan' => 'pro',
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        $original = ActivationCode::create([
            'tenant_id' => $tenant->id,
            'code' => ActivationCode::generate(),
            'plan' => 'pro',
            'intended_admin_name' => 'Original Admin',
            'intended_admin_email' => 'orig@regen.test',
            'status' => 'unredeemed',
            'expires_at' => now()->addDays(30),
        ]);

        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        try {
            $response = $this->actingAs($admin, 'super_admin')
                ->post("http://admin.localhost/tenants/{$tenant->id}/codes/regenerate");

            $response->assertRedirect();

            $original->refresh();
            $this->assertSame('revoked', $original->status);
            $this->assertNotNull($original->revoked_at);

            $newCode = $tenant->activationCodes()->where('status', 'unredeemed')->latest()->first();
            $this->assertNotNull($newCode);
            $this->assertNotSame($original->code, $newCode->code);
            $this->assertSame('pro', $newCode->plan);
            $this->assertSame('orig@regen.test', $newCode->intended_admin_email);
        } finally {
            $tenant->activationCodes()->delete();
            $tenant->domains()->delete();
            $tenant->delete();
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$database}`");
        }
    }

    public function test_revoke_marks_code_revoked_without_creating_a_new_one(): void
    {
        $subdomain = 'revtest' . substr(md5(uniqid()), 0, 6);
        $database = 'tenant_rev_' . substr(md5(uniqid()), 0, 6);
        DB::connection('central')->statement("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $tenant = Tenant::create([
            'name' => 'Revoke Test',
            'subdomain' => $subdomain,
            'database' => $database,
            'status' => 'pending_activation',
            'plan' => 'free',
        ]);
        $tenant->domains()->create(['domain' => $subdomain]);

        $code = ActivationCode::create([
            'tenant_id' => $tenant->id,
            'code' => ActivationCode::generate(),
            'plan' => 'free',
            'intended_admin_name' => 'X',
            'intended_admin_email' => 'x@rev.test',
            'status' => 'unredeemed',
            'expires_at' => now()->addDays(30),
        ]);

        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        try {
            $response = $this->actingAs($admin, 'super_admin')
                ->post("http://admin.localhost/tenants/{$tenant->id}/codes/{$code->id}/revoke");

            $response->assertRedirect();

            $code->refresh();
            $this->assertSame('revoked', $code->status);

            $unredeemedCount = $tenant->activationCodes()->where('status', 'unredeemed')->count();
            $this->assertSame(0, $unredeemedCount, 'Revoke should NOT create a replacement code');
        } finally {
            $tenant->activationCodes()->delete();
            $tenant->domains()->delete();
            $tenant->delete();
            DB::connection('central')->statement("DROP DATABASE IF EXISTS `{$database}`");
        }
    }
}
