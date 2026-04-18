@auth
<div
    class="relative"
    x-data="announcementBell({
        unreadCount: {{ (int) ($unreadAnnouncementCount ?? 0) }},
        visibleIds: @js(($activeAnnouncements ?? collect())->pluck('id')->all()),
        readIds: @js($announcementReadIds ?? []),
        batchUrl: '{{ route('announcements.read-batch') }}',
        csrf: '{{ csrf_token() }}',
    })"
>
    <button
        type="button"
        class="relative grid h-10 w-10 place-items-center rounded-full border border-gray-200 bg-white hover:border-primary-light hover:shadow-sm transition"
        @click="toggle()"
        aria-label="Announcements"
    >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-600"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 01-3.46 0"/></svg>
        <template x-if="count > 0">
            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] rounded-full bg-red-500 text-white text-[11px] font-bold grid place-items-center px-1" x-text="count > 99 ? '99+' : count"></span>
        </template>
    </button>

    <div
        x-show="open"
        x-cloak
        @click.outside="open = false"
        class="absolute right-0 mt-2 w-[360px] max-w-[90vw] z-[2500] rounded-xl border border-gray-200 bg-white shadow-xl overflow-hidden"
    >
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <div class="text-sm font-semibold text-slate-800">Announcements</div>
            <a href="{{ route('announcements.index') }}" class="text-xs text-primary hover:underline">View all</a>
        </div>
        <div class="max-h-[400px] overflow-y-auto">
            @forelse(($activeAnnouncements ?? collect())->take(5) as $a)
                <a href="{{ route('announcements.show', $a) }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50">
                    <div class="flex items-start gap-2">
                        @if($a->priority === 'critical')
                            <span class="mt-1 inline-block h-2 w-2 rounded-full bg-red-500 flex-shrink-0"></span>
                        @elseif($a->priority === 'info')
                            <span class="mt-1 inline-block h-2 w-2 rounded-full bg-blue-500 flex-shrink-0"></span>
                        @else
                            <span class="mt-1 inline-block h-2 w-2 rounded-full bg-amber-500 flex-shrink-0"></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-slate-800 truncate">{{ $a->title }}</div>
                            <div class="text-xs text-slate-500 line-clamp-2 mt-0.5">{{ \Illuminate\Support\Str::limit(strip_tags($a->body_html), 100) }}</div>
                            <div class="text-[11px] text-slate-400 mt-1">{{ ($a->publish_at ?? $a->created_at)->diffForHumans() }}</div>
                        </div>
                    </div>
                </a>
            @empty
                <div class="px-4 py-8 text-center text-sm text-slate-500">No announcements.</div>
            @endforelse
        </div>
    </div>
</div>

<script data-turbo-permanent>
document.addEventListener('alpine:init', () => {
    Alpine.data('announcementBell', (init) => ({
        open: false,
        count: init.unreadCount,
        visibleIds: init.visibleIds,
        readIds: init.readIds,
        batchUrl: init.batchUrl,
        csrf: init.csrf,
        toggle() {
            this.open = !this.open;
            if (this.open && this.count > 0) {
                this.markRead();
            }
        },
        markRead() {
            const unread = this.visibleIds.filter(id => !this.readIds.includes(id));
            if (unread.length === 0) return;
            this.count = 0;
            fetch(this.batchUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ ids: unread }),
            }).catch(() => {});
        }
    }));
});
</script>
@endauth
