<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AnnouncementRead;
use App\Services\AnnouncementVisibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function __construct(private AnnouncementVisibility $visibility) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $list = $this->visibility->activeFor($user);

        $this->upsertRead(
            $user->id,
            $list->where('priority', '!=', 'critical')->pluck('id')->all()
        );

        return view('announcements.index', ['announcements' => $list]);
    }

    public function show(Request $request, Announcement $announcement): View
    {
        $this->ensureVisible($request, $announcement);

        if ($announcement->priority !== 'critical') {
            $this->upsertRead($request->user()->id, [$announcement->id]);
        }

        return view('announcements.show', ['announcement' => $announcement]);
    }

    public function markRead(Request $request, Announcement $announcement): JsonResponse
    {
        $this->ensureVisible($request, $announcement);

        if ($announcement->priority === 'critical') {
            return response()->json(['ok' => false], 422);
        }
        $this->upsertRead($request->user()->id, [$announcement->id]);

        return response()->json(['ok' => true]);
    }

    public function markReadBatch(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $visible = $this->visibility->activeFor($request->user())->pluck('id')->all();
        $allowed = array_values(array_intersect($ids, $visible));

        $critical = Announcement::whereIn('id', $allowed)
            ->where('priority', 'critical')->pluck('id')->all();
        $toMark = array_values(array_diff($allowed, $critical));

        $this->upsertRead($request->user()->id, $toMark);

        return response()->json(['ok' => true, 'count' => count($toMark)]);
    }

    public function acknowledge(Request $request, Announcement $announcement): RedirectResponse|JsonResponse
    {
        $this->ensureVisible($request, $announcement);

        if ($announcement->priority !== 'critical') {
            return response()->json(['ok' => false, 'error' => 'Only critical announcements require acknowledgement.'], 422);
        }

        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => $request->user()->id],
            ['read_at' => now(), 'acknowledged_at' => now()],
        );

        \App\Models\AuditLog::log(
            action: 'acknowledged',
            description: "Acknowledged announcement: {$announcement->title}",
            model: $announcement,
        );

        return redirect()->back()->with('status', 'Thanks — marked as read.');
    }

    private function ensureVisible(Request $request, Announcement $announcement): void
    {
        $visible = $this->visibility->activeFor($request->user())->pluck('id')->all();
        if (! in_array($announcement->id, $visible, true)) {
            abort(403, 'This announcement is not visible to you.');
        }
    }

    private function upsertRead(int $userId, array $announcementIds): void
    {
        if (empty($announcementIds)) {
            return;
        }
        $now = now();
        $rows = array_map(fn ($id) => [
            'announcement_id' => $id,
            'user_id'         => $userId,
            'read_at'         => $now,
            'acknowledged_at' => null,
            'created_at'      => $now,
            'updated_at'      => $now,
        ], $announcementIds);

        DB::table('announcement_reads')->upsert(
            $rows,
            ['announcement_id', 'user_id'],
            ['read_at', 'updated_at']
        );
    }
}
