<?php

namespace App\View\Composers;

use App\Services\AnnouncementVisibility;
use Illuminate\View\View;

class AnnouncementComposer
{
    public function __construct(private AnnouncementVisibility $visibility) {}

    public function compose(View $view): void
    {
        $user = auth()->user();

        if ($user) {
            $active = $this->visibility->activeFor($user);
            $readIds = \App\Models\AnnouncementRead::where('user_id', $user->id)
                ->whereIn('announcement_id', $active->pluck('id'))
                ->pluck('announcement_id')->all();

            $ackedIds = \App\Models\AnnouncementRead::where('user_id', $user->id)
                ->whereIn('announcement_id', $active->pluck('id'))
                ->whereNotNull('acknowledged_at')
                ->pluck('announcement_id')->all();

            $criticalUnacked = $active
                ->where('priority', 'critical')
                ->whereNotIn('id', $ackedIds)
                ->values();

            $view
                ->with('activeAnnouncements', $active)
                ->with('announcementReadIds', $readIds)
                ->with('criticalUnacked', $criticalUnacked)
                ->with('unreadAnnouncementCount', $active->count() - count($readIds));
        } else {
            $loginList = $this->visibility->activeForLogin();
            $dismissedIds = collect(explode(',', (string) request()->cookie('ann_dismissed', '')))
                ->filter()->map(fn ($v) => (int) $v);
            $view->with('loginAnnouncements', $loginList->whereNotIn('id', $dismissedIds)->values());
        }
    }
}
