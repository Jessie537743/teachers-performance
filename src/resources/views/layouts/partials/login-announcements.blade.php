@if(isset($loginAnnouncements) && $loginAnnouncements->isNotEmpty())
    <div id="login-announcements" class="space-y-2 mb-4">
        @foreach($loginAnnouncements as $a)
            @php
                $cardTheme = [
                    'critical' => ['card' => 'border-red-200 bg-red-50 text-red-900',     'btn' => 'text-red-400 hover:text-red-700'],
                    'normal'   => ['card' => 'border-amber-200 bg-amber-50 text-amber-900','btn' => 'text-amber-400 hover:text-amber-700'],
                    'info'     => ['card' => 'border-blue-200 bg-blue-50 text-blue-900',  'btn' => 'text-blue-400 hover:text-blue-700'],
                ][$a->priority] ?? ['card' => 'border-slate-200 bg-slate-50 text-slate-900', 'btn' => 'text-slate-400 hover:text-slate-700'];
            @endphp
            <div id="login-ann-{{ $a->id }}" class="rounded-lg border {{ $cardTheme['card'] }} p-3 text-sm relative">
                <button type="button" aria-label="Dismiss" onclick="dismissLoginAnnouncement({{ $a->id }})" class="absolute right-2 top-2 {{ $cardTheme['btn'] }}">&times;</button>
                <div class="flex items-center gap-2 pr-6">
                    @if($a->priority === 'critical')
                        <span class="inline-flex items-center rounded bg-red-100 text-red-800 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide">Critical</span>
                    @elseif($a->priority === 'info')
                        <span class="inline-flex items-center rounded bg-blue-100 text-blue-800 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide">Info</span>
                    @endif
                    <div class="font-semibold">{{ $a->title }}</div>
                </div>
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
