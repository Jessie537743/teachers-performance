<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnnouncementVisibility
{
    /** Returns announcements currently visible to the given user, ordered for display. */
    public function activeFor(User $user): Collection
    {
        $roles         = $user->roles ?? [];
        $departmentId  = (string) ($user->department_id ?? '');
        $userId        = (string) $user->id;

        $matches = function ($q, bool $exclude) use ($roles, $departmentId, $userId) {
            $q->from('announcement_targets as t')
                ->whereColumn('t.announcement_id', 'announcements.id')
                ->where('t.is_exclude', $exclude)
                ->where(function ($q) use ($roles, $departmentId, $userId) {
                    $q->where(function ($q) use ($roles) {
                        $q->where('t.target_type', 'role')
                          ->whereIn('t.target_id', $roles ?: ['__none__']);
                    });
                    if ($departmentId !== '') {
                        $q->orWhere(function ($q) use ($departmentId) {
                            $q->where('t.target_type', 'department')
                              ->where('t.target_id', $departmentId);
                        });
                    }
                    $q->orWhere(function ($q) use ($userId) {
                        $q->where('t.target_type', 'user')
                          ->where('t.target_id', $userId);
                    });
                });
        };

        return Announcement::query()
            ->active()
            ->where(function ($q) use ($matches) {
                $q->where('everyone', true)
                  ->orWhereExists(fn ($q) => $matches($q, false));
            })
            ->whereNotExists(fn ($q) => $matches($q, true))
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByDesc(DB::raw('COALESCE(publish_at, created_at)'))
            ->get();
    }

    /** Up to 3 login-page-flagged announcements, target rules ignored. */
    public function activeForLogin(): Collection
    {
        return Announcement::query()
            ->active()
            ->where('show_on_login', true)
            ->orderByDesc('is_pinned')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'normal' THEN 1 ELSE 2 END")
            ->orderByDesc(DB::raw('COALESCE(publish_at, created_at)'))
            ->limit(3)
            ->get();
    }

    /** Count of announcements the user can see but has no read row for. */
    public function unreadCountFor(User $user): int
    {
        $visibleIds = $this->activeFor($user)->pluck('id');
        if ($visibleIds->isEmpty()) {
            return 0;
        }

        $readIds = \App\Models\AnnouncementRead::query()
            ->where('user_id', $user->id)
            ->whereIn('announcement_id', $visibleIds)
            ->pluck('announcement_id');

        return $visibleIds->diff($readIds)->count();
    }
}
