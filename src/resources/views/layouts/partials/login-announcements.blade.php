@if(isset($loginAnnouncements) && $loginAnnouncements->isNotEmpty())
    <div id="login-announcements" class="space-y-2">
        @foreach($loginAnnouncements as $a)
            <div id="login-ann-{{ $a->id }}" class="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-900 relative">
                <button type="button" aria-label="Dismiss" onclick="dismissLoginAnnouncement({{ $a->id }})" class="absolute right-2 top-2 text-blue-400 hover:text-blue-700">&times;</button>
                <div class="font-semibold pr-6">{{ $a->title }}</div>
                <div class="mt-1 prose prose-sm max-w-none">{!! $a->body_html !!}</div>
            </div>
        @endforeach
    </div>
    <script>
        function dismissLoginAnnouncement(id) {
            const existing = (document.cookie.split('; ').find(r => r.startsWith('ann_dismissed=')) || '').replace('ann_dismissed=', '');
            const ids = existing ? existing.split(',') : [];
            if (!ids.includes(String(id))) ids.push(String(id));
            const expires = new Date(Date.now() + 86400000).toUTCString();
            document.cookie = 'ann_dismissed=' + ids.join(',') + '; path=/; expires=' + expires + '; SameSite=Lax';
            const el = document.getElementById('login-ann-' + id);
            if (el) el.remove();
        }
    </script>
@endif
