<?php

namespace Tests\Feature\Announcements;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadStateTest extends TestCase
{
    use RefreshDatabase;

    public function test_archive_index_marks_non_critical_read(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]);

        $this->actingAs($u)->get(route('announcements.index'))->assertOk();

        $this->assertDatabaseHas('announcement_reads', [
            'announcement_id' => $a->id,
            'user_id'         => $u->id,
        ]);
    }

    public function test_archive_index_does_not_auto_ack_critical(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->critical()->create(['everyone' => true]);

        $this->actingAs($u)->get(route('announcements.index'))->assertOk();

        $read = AnnouncementRead::where('announcement_id', $a->id)->where('user_id', $u->id)->first();
        $this->assertNull($read, 'critical must not be auto-marked-read');
    }

    public function test_batch_mark_read_sets_read_at_for_submitted_ids(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]);
        $b = Announcement::factory()->create(['everyone' => true]);

        $this->actingAs($u)->post(route('announcements.read-batch'), [
            'ids' => [$a->id, $b->id],
        ])->assertOk();

        $this->assertSame(2, AnnouncementRead::where('user_id', $u->id)->count());
    }

    public function test_acknowledge_sets_acknowledged_at(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->critical()->create(['everyone' => true]);

        $this->actingAs($u)->post(route('announcements.ack', $a))->assertRedirect();

        $this->assertNotNull(
            AnnouncementRead::where('announcement_id', $a->id)->where('user_id', $u->id)->value('acknowledged_at')
        );
    }

    public function test_acknowledge_rejected_for_non_critical(): void
    {
        $u = User::factory()->create(['roles' => ['faculty']]);
        $a = Announcement::factory()->create(['everyone' => true]);

        $this->actingAs($u)->post(route('announcements.ack', $a))->assertStatus(422);

        $this->assertDatabaseMissing('announcement_reads', [
            'announcement_id' => $a->id,
            'user_id' => $u->id,
        ]);
    }

    public function test_user_cannot_mark_announcement_they_cannot_see(): void
    {
        $u = User::factory()->create(['roles' => ['student']]);
        $a = Announcement::factory()->create(['everyone' => false]);
        \App\Models\AnnouncementTarget::create([
            'announcement_id' => $a->id,
            'target_type' => 'role',
            'target_id' => 'dean',
            'is_exclude' => false,
        ]);

        $this->actingAs($u)->post(route('announcements.read', $a))->assertForbidden();
    }
}
