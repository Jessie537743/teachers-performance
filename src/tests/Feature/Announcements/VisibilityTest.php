<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\AnnouncementTarget;
use App\Models\Department;
use App\Models\User;
use App\Services\AnnouncementVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisibilityTest extends TestCase
{
    use RefreshDatabase;

    private AnnouncementVisibility $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AnnouncementVisibility();
    }

    public function test_everyone_flag_makes_announcement_visible_to_any_user(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]);

        $visible = $this->service->activeFor($user)->pluck('id');
        $this->assertTrue($visible->contains($a->id));
    }

    public function test_role_target_matches_user_with_that_role(): void
    {
        $user = User::factory()->create(['roles' => ['dean']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'dean',
            'is_exclude'      => false,
        ]);

        $this->assertTrue($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_role_target_excludes_user_without_that_role(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'dean',
            'is_exclude'      => false,
        ]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_department_target_matches_user_in_that_department(): void
    {
        $dept = Department::factory()->create();
        $user = User::factory()->create(['roles' => ['faculty'], 'department_id' => $dept->id]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'department',
            'target_id'       => (string) $dept->id,
            'is_exclude'      => false,
        ]);

        $this->assertTrue($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_user_target_matches_specific_user(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'user',
            'target_id'       => (string) $user->id,
            'is_exclude'      => false,
        ]);

        $this->assertTrue($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_exclude_rule_overrides_everyone(): void
    {
        $user = User::factory()->create(['roles' => ['student']]);
        $a = Announcement::factory()->create(['everyone' => true]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'student',
            'is_exclude'      => true,
        ]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_draft_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->draft()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_scheduled_in_future_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->scheduled()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_expired_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->expired()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_archived_is_not_visible(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->archived()->create(['everyone' => true]);

        $this->assertFalse($this->service->activeFor($user)->pluck('id')->contains($a->id));
    }

    public function test_ordering_pinned_critical_then_recent(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $old     = Announcement::factory()->create(['everyone' => true, 'publish_at' => now()->subDays(5)]);
        $pinned  = Announcement::factory()->create(['everyone' => true, 'is_pinned' => true, 'publish_at' => now()->subDays(10)]);
        $critical= Announcement::factory()->critical()->create(['everyone' => true, 'publish_at' => now()->subDays(2)]);

        $ordered = $this->service->activeFor($user)->pluck('id')->all();

        $this->assertSame([$pinned->id, $critical->id, $old->id], $ordered);
    }

    public function test_activeForLogin_only_returns_show_on_login_flagged(): void
    {
        Announcement::factory()->create(['everyone' => true, 'show_on_login' => false]);
        $loginOnly = Announcement::factory()->loginVisible()->create(['everyone' => true]);

        $ids = $this->service->activeForLogin()->pluck('id')->all();
        $this->assertSame([$loginOnly->id], $ids);
    }

    public function test_activeForLogin_ignores_targets_and_audience(): void
    {
        $a = Announcement::factory()->loginVisible()->create(['everyone' => false]);
        AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type'     => 'role',
            'target_id'       => 'dean',
            'is_exclude'      => false,
        ]);

        $ids = $this->service->activeForLogin()->pluck('id');
        $this->assertTrue($ids->contains($a->id));
    }

    public function test_activeForLogin_respects_lifecycle(): void
    {
        Announcement::factory()->loginVisible()->draft()->create();
        Announcement::factory()->loginVisible()->expired()->create();
        Announcement::factory()->loginVisible()->scheduled()->create();
        $live = Announcement::factory()->loginVisible()->create();

        $this->assertEquals([$live->id], $this->service->activeForLogin()->pluck('id')->all());
    }

    public function test_activeForLogin_limits_to_three(): void
    {
        Announcement::factory()->count(5)->loginVisible()->create(['publish_at' => now()->subMinutes(1)]);
        $this->assertCount(3, $this->service->activeForLogin());
    }

    public function test_unreadCountFor_returns_visible_minus_read(): void
    {
        $user = User::factory()->create(['roles' => ['faculty']]);
        $a1 = Announcement::factory()->create(['everyone' => true]);
        Announcement::factory()->create(['everyone' => true]);
        Announcement::factory()->create(['everyone' => true]);
        AnnouncementRead::create([
            'announcement_id' => $a1->id,
            'user_id'         => $user->id,
            'read_at'         => now(),
        ]);

        $this->assertSame(2, $this->service->unreadCountFor($user));
    }
}
