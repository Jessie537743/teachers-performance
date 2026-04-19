<?php

namespace Tests\Feature\SuperAdmin;

use App\Models\SuperAdmin;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(PreventRequestForgery::class);
    }

    public function test_login_page_renders_on_admin_subdomain(): void
    {
        $response = $this->get('http://admin.localhost/login');

        $response->assertOk();
        $response->assertSee('Platform Console');
    }

    public function test_super_admin_can_authenticate_with_correct_credentials(): void
    {
        $admin = SuperAdmin::where('email', 'super@platform.test')->firstOrFail();

        $response = $this->post('http://admin.localhost/login', [
            'email'    => 'super@platform.test',
            'password' => 'super123',
        ]);

        $response->assertRedirect('http://admin.localhost/tenants');
        $this->assertAuthenticatedAs($admin, 'super_admin');
    }

    public function test_invalid_credentials_are_rejected(): void
    {
        $response = $this->post('http://admin.localhost/login', [
            'email'    => 'super@platform.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest('super_admin');
    }

    public function test_dashboard_redirects_unauthenticated_users_to_login(): void
    {
        $response = $this->get('http://admin.localhost/tenants');

        $response->assertRedirect('http://admin.localhost/login');
    }
}
