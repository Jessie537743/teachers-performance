<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\User;
use App\Policies\AnnouncementPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private AnnouncementPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        // Migrations insert only partial role_permissions rows; truncate and re-seed
        // full defaults so Permission::forRole() resolves correctly in SQLite.
        \App\Models\RolePermission::truncate();
        foreach (\App\Enums\Permission::allRoles() as $role) {
            foreach (\App\Enums\Permission::defaultsForRole($role) as $permission) {
                \App\Models\RolePermission::create(['role' => $role, 'permission' => $permission]);
            }
        }
        \App\Enums\Permission::clearCache();
        $this->policy = new AnnouncementPolicy();
    }

    public function test_system_author_can_create_any_announcement(): void
    {
        $sys = User::factory()->create(['roles' => ['human_resource']]);
        $this->assertTrue($this->policy->create($sys));
    }

    public function test_department_author_can_create(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);
        $this->assertTrue($this->policy->create($head));
    }

    public function test_random_user_cannot_create(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $this->assertFalse($this->policy->create($u));
    }

    public function test_department_author_cannot_target_everyone(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => true,
            'targets'  => [],
        ]));
    }

    public function test_department_author_can_target_own_department(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);

        $this->assertTrue($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'department', 'target_id' => (string) $dept->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_cannot_target_other_department(): void
    {
        $a = Department::factory()->create();
        $b = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $a->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'department', 'target_id' => (string) $b->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_cannot_use_role_target(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'role', 'target_id' => 'faculty', 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_can_target_user_in_own_department(): void
    {
        $dept = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);
        $member = User::factory()->create(['department_id' => $dept->id]);

        $this->assertTrue($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'user', 'target_id' => (string) $member->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_department_author_cannot_target_user_in_other_department(): void
    {
        $a = Department::factory()->create();
        $b = Department::factory()->create();
        $head = User::factory()->create(['roles' => ['head'], 'department_id' => $a->id]);
        $member = User::factory()->create(['department_id' => $b->id]);

        $this->assertFalse($this->policy->validateTargeting($head, [
            'everyone' => false,
            'targets'  => [
                ['target_type' => 'user', 'target_id' => (string) $member->id, 'is_exclude' => false],
            ],
        ]));
    }

    public function test_system_author_has_no_target_restrictions(): void
    {
        $sys = User::factory()->create(['roles' => ['vp_acad']]);
        $this->assertTrue($this->policy->validateTargeting($sys, [
            'everyone' => true,
            'targets'  => [
                ['target_type' => 'role', 'target_id' => 'faculty', 'is_exclude' => false],
            ],
        ]));
    }

    public function test_only_author_or_system_can_update_own_scope(): void
    {
        $dept = Department::factory()->create();
        $author = User::factory()->create(['roles' => ['head'], 'department_id' => $dept->id]);
        $other  = User::factory()->create(['roles' => ['head'], 'department_id' => Department::factory()->create()->id]);
        $sys    = User::factory()->create(['roles' => ['human_resource']]);

        $a = Announcement::factory()->create(['created_by' => $author->id]);

        $this->assertTrue($this->policy->update($author, $a));
        $this->assertFalse($this->policy->update($other, $a));
        $this->assertTrue($this->policy->update($sys, $a));
    }
}
