<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\Department;
use App\Models\RolePermission;
use App\Models\User;
use App\Enums\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF verification for feature tests that use POST/PUT/DELETE.
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class);

        // Ensure role_permissions reflects Permission::defaultsForRole() (see Batch A notes).
        RolePermission::query()->delete();
        foreach (Permission::allRoles() as $role) {
            foreach (Permission::defaultsForRole($role) as $perm) {
                RolePermission::create(['role' => $role, 'permission' => $perm]);
            }
        }
        Permission::clearCache();
    }

    public function test_system_author_can_store_announcement(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);

        $this->actingAs($sys)->post(route('admin.announcements.store'), [
            'title'         => 'Hello',
            'body_markdown' => '**hi**',
            'priority'      => 'normal',
            'everyone'      => true,
            'show_on_login' => false,
            'status'        => 'published',
        ])->assertRedirect();

        $this->assertDatabaseHas('announcements', [
            'title'      => 'Hello',
            'created_by' => $sys->id,
            'status'     => 'published',
        ]);

        $a = Announcement::firstWhere('title', 'Hello');
        $this->assertStringContainsString('<strong>hi</strong>', $a->body_html);
    }

    public function test_store_rejects_out_of_scope_for_dept_author(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);

        $this->actingAs($head)->post(route('admin.announcements.store'), [
            'title'         => 'x',
            'body_markdown' => 'x',
            'priority'      => 'normal',
            'everyone'      => true,
            'status'        => 'published',
        ])->assertSessionHasErrors('targets');
    }

    public function test_update_without_notify_preserves_reads(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);
        $reader = User::factory()->create(['roles' => ['faculty']]);
        AnnouncementRead::create([
            'announcement_id' => $a->id,
            'user_id' => $reader->id,
            'read_at' => now(),
        ]);

        $this->actingAs($sys)->put(route('admin.announcements.update', $a), [
            'title'         => 'Updated',
            'body_markdown' => $a->body_markdown,
            'priority'      => $a->priority,
            'everyone'      => $a->everyone,
            'status'        => $a->status,
        ])->assertRedirect();

        $this->assertDatabaseCount('announcement_reads', 1);
    }

    public function test_update_with_notify_again_resets_reads(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);
        $reader = User::factory()->create(['roles' => ['faculty']]);
        AnnouncementRead::create([
            'announcement_id' => $a->id,
            'user_id' => $reader->id,
            'read_at' => now(),
        ]);

        $this->actingAs($sys)->put(route('admin.announcements.update', $a), [
            'title'         => 'Updated',
            'body_markdown' => 'New body',
            'priority'      => $a->priority,
            'everyone'      => $a->everyone,
            'status'        => $a->status,
            'notify_again'  => true,
        ])->assertRedirect();

        $this->assertDatabaseCount('announcement_reads', 0);
    }

    public function test_archive_action_sets_status_archived(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);

        $this->actingAs($sys)->post(route('admin.announcements.archive', $a))
            ->assertRedirect();

        $this->assertSame('archived', $a->fresh()->status);
    }

    public function test_delete_removes_row_and_targets(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $a = Announcement::factory()->create(['created_by' => $sys->id]);
        \App\Models\AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'faculty',
            'is_exclude'      => false,
        ]);

        $this->actingAs($sys)->delete(route('admin.announcements.destroy', $a))
            ->assertRedirect();

        $this->assertDatabaseMissing('announcements', ['id' => $a->id]);
        $this->assertDatabaseCount('announcement_targets', 0);
    }
}
