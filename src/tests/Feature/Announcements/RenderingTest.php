<?php

namespace Tests\Feature\Announcements;

use App\Enums\Permission;
use App\Models\Announcement;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Tests\TestCase;

class RenderingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PreventRequestForgery::class);

        // Seed role_permissions for tests (SQLite in-memory).
        RolePermission::query()->delete();
        foreach (Permission::allRoles() as $role) {
            foreach (Permission::defaultsForRole($role) as $perm) {
                RolePermission::create(['role' => $role, 'permission' => $perm]);
            }
        }
        Permission::clearCache();
    }

    public function test_markdown_stored_as_sanitized_html_on_create(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);

        $this->actingAs($sys)->post(route('admin.announcements.store'), [
            'title'         => 'Launch',
            'body_markdown' => "# Not allowed\n\n<script>alert(1)</script>\n\n**ok** [site](https://x.y)",
            'priority'      => 'normal',
            'everyone'      => true,
            'status'        => 'published',
        ])->assertRedirect();

        $a = Announcement::firstWhere('title', 'Launch');
        $this->assertNotNull($a, 'Announcement was not created');
        $this->assertStringContainsString('<strong>ok</strong>', $a->body_html);
        $this->assertStringContainsString('href="https://x.y"', $a->body_html);
        $this->assertStringNotContainsString('<script', $a->body_html);
        $this->assertMatchesRegularExpression('/rel="[^"]*noopener[^"]*"/', $a->body_html);
        // h1 is not in the allowlist — should be stripped.
        $this->assertStringNotContainsString('<h1>', $a->body_html);
    }
}
