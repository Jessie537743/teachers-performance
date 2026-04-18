@php
    $a = $announcement ?? null;
    $initialTargets = $a ? $a->targets->map(fn($t) => [
        'target_type' => $t->target_type,
        'target_id'   => (string) $t->target_id,
        'is_exclude'  => (bool) $t->is_exclude,
    ])->values()->all() : [];
@endphp

<form method="POST" action="{{ $action }}" class="space-y-4">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
        <input type="text" name="title" required maxlength="200" value="{{ old('title', $a->title ?? '') }}"
               class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-primary focus:ring-primary">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Body (markdown)</label>
        <div class="grid md:grid-cols-2 gap-2">
            <textarea name="body_markdown" rows="10" id="body-source"
                      class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono">{{ old('body_markdown', $a->body_markdown ?? '') }}</textarea>
            <div id="body-preview" class="prose prose-sm max-w-none rounded-md border border-gray-200 bg-gray-50 px-3 py-2 overflow-auto max-h-[400px]">
                <em class="text-slate-400">Preview renders here.</em>
            </div>
        </div>
    </div>

    <div class="grid md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Priority</label>
            <select name="priority" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @foreach(['info','normal','critical'] as $p)
                    <option value="{{ $p }}" @selected(old('priority', $a->priority ?? 'normal') === $p)>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
            <select name="status" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                @foreach(($method === 'PUT' ? ['draft','published','archived'] : ['draft','published']) as $s)
                    <option value="{{ $s }}" @selected(old('status', $a->status ?? 'published') === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-end gap-4">
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $a?->is_pinned)) class="rounded">
                Pinned
            </label>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="show_on_login" value="1" @checked(old('show_on_login', $a?->show_on_login)) class="rounded">
                Show on login
            </label>
        </div>
    </div>

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Publish at (optional)</label>
            <input type="datetime-local" name="publish_at"
                   value="{{ old('publish_at', $a?->publish_at?->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Expires at (optional)</label>
            <input type="datetime-local" name="expires_at"
                   value="{{ old('expires_at', $a?->expires_at?->format('Y-m-d\TH:i')) }}"
                   class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
    </div>

    <fieldset class="rounded-lg border border-gray-200 p-4">
        <legend class="text-sm font-semibold px-1">Audience</legend>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="everyone" value="1" @checked(old('everyone', $a?->everyone)) class="rounded">
            Everyone (overrides role/department/user targeting except excludes)
        </label>

        <div class="mt-3 grid md:grid-cols-3 gap-3 text-sm">
            <div>
                <div class="font-medium mb-1">Roles</div>
                <select multiple size="6" class="w-full rounded-md border border-gray-300" id="roles-include">
                    @foreach($allRoles as $r)
                        <option value="{{ $r }}">{{ \App\Enums\Permission::roleLabel($r) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="font-medium mb-1">Departments</div>
                <select multiple size="6" class="w-full rounded-md border border-gray-300" id="depts-include">
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}">{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <div class="font-medium mb-1">Users</div>
                <select multiple size="6" class="w-full rounded-md border border-gray-300" id="users-include">
                    @foreach($userOptions as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-3 text-xs text-slate-500">
            Excludes: if you need to carve out specific roles/departments/users, add them as <code>is_exclude</code> entries in the raw targets field below.
        </div>

        <details class="mt-3">
            <summary class="cursor-pointer text-sm font-medium text-slate-700">Raw targets JSON (advanced)</summary>
            <textarea name="targets_raw" rows="5" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm font-mono mt-2"
                      placeholder='[{"target_type":"role","target_id":"dean","is_exclude":false}]'>{{ old('targets_raw', json_encode($initialTargets)) }}</textarea>
            <p class="text-xs text-slate-500 mt-1">On submit, this takes precedence over the select boxes above.</p>
        </details>
    </fieldset>

    @if($method === 'PUT')
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" name="notify_again" value="1" class="rounded">
            Notify readers again (resets read state if body/priority changed)
        </label>
    @endif

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
            </ul>
        </div>
    @endif

    <div class="flex items-center gap-2">
        <button type="submit" class="inline-flex items-center rounded-md bg-primary text-white px-4 py-2 text-sm font-semibold hover:bg-primary-dark transition">Save</button>
        <a href="{{ route('admin.announcements.index') }}" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-4 py-2 text-sm hover:bg-gray-50">Cancel</a>
    </div>
</form>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script>
    (function () {
        const src = document.getElementById('body-source');
        const out = document.getElementById('body-preview');
        function renderPreview() {
            out.innerHTML = window.marked.parse(src.value || '*Preview renders here.*');
        }
        src.addEventListener('input', renderPreview);
        renderPreview();

        const form = src.closest('form');
        form.addEventListener('submit', function (e) {
            const rawEl = form.querySelector('[name="targets_raw"]');
            let targets = [];
            try { targets = JSON.parse(rawEl.value || '[]'); } catch { targets = []; }

            const roles = Array.from(document.getElementById('roles-include').selectedOptions).map(o => o.value);
            const depts = Array.from(document.getElementById('depts-include').selectedOptions).map(o => o.value);
            const users = Array.from(document.getElementById('users-include').selectedOptions).map(o => o.value);

            const fromSelects = [
                ...roles.map(v => ({ target_type: 'role',       target_id: v, is_exclude: false })),
                ...depts.map(v => ({ target_type: 'department', target_id: v, is_exclude: false })),
                ...users.map(v => ({ target_type: 'user',       target_id: v, is_exclude: false })),
            ];

            const final = fromSelects.length > 0 ? fromSelects.concat(targets.filter(t => t.is_exclude)) : targets;

            rawEl.remove();
            final.forEach((t, i) => {
                for (const k of ['target_type', 'target_id', 'is_exclude']) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `targets[${i}][${k}]`;
                    input.value = k === 'is_exclude' ? (t[k] ? 1 : 0) : t[k];
                    form.appendChild(input);
                }
            });
        });
    })();
</script>
