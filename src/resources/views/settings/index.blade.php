@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Manage application settings and view all users.</p>
    </div>
</div>

{{-- Tab Navigation --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-6">
    <div class="flex gap-0 border-b border-gray-200">
        <a href="{{ route('settings.index', ['tab' => 'general']) }}"
           class="px-6 py-3.5 font-semibold text-sm no-underline transition {{ $tab === 'general' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            General
        </a>
        <a href="{{ route('settings.index', ['tab' => 'users']) }}"
           class="px-6 py-3.5 font-semibold text-sm no-underline transition {{ $tab === 'users' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            Users
        </a>
        <a href="{{ route('settings.index', ['tab' => 'signatures']) }}"
           class="px-6 py-3.5 font-semibold text-sm no-underline transition {{ $tab === 'signatures' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            HR Signatories
        </a>
    </div>

    {{-- General Tab --}}
    @if($tab === 'general')
    <div class="p-6">
        <form method="POST" action="{{ route('settings.update-general') }}" enctype="multipart/form-data">
            @csrf

            {{-- App Name --}}
            <div class="mb-4 max-w-[480px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="app_name">Application Name</label>
                <input type="text" name="app_name" id="app_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       value="{{ old('app_name', $appName) }}" required maxlength="100"
                       placeholder="e.g. Evaluation System">
                <div class="text-xs text-gray-400 mt-1">
                    Displayed in the sidebar, page titles, and login page.
                </div>
            </div>

            {{-- App Logo --}}
            <div class="mb-4 max-w-[480px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Application Logo</label>

                {{-- Current Logo Preview --}}
                <div class="flex items-center gap-4 mb-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="w-16 h-16 rounded-xl bg-white border border-gray-200 grid place-items-center overflow-hidden shrink-0">
                        @if($appLogo)
                            <img src="{{ asset('storage/' . $appLogo) }}" alt="App Logo"
                                 class="max-w-[56px] max-h-[56px] object-contain">
                        @else
                            <img src="{{ asset(config('app.default_logo')) }}" alt="Default Logo"
                                 class="max-w-[56px] max-h-[56px] object-contain">
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">
                            {{ $appLogo ? 'Custom Logo' : 'Default Logo' }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $appLogo ? 'Using uploaded logo' : 'Using default SMCC logo' }}
                        </div>
                    </div>
                    @if($appLogo)
                    <form method="POST" action="{{ route('settings.remove-logo') }}" class="ml-auto"
                          onsubmit="event.preventDefault(); showConfirm('Remove the custom logo? The default logo will be used instead.', this, {confirmText: 'Remove'})">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold hover:bg-red-700 transition">Remove</button>
                    </form>
                    @endif
                </div>

                <input type="file" name="app_logo" id="app_logo" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       accept="image/png,image/jpeg,image/svg+xml,image/webp">
                <div class="text-xs text-gray-400 mt-1">
                    Accepted formats: PNG, JPG, SVG, WebP. Max size: 2MB.
                </div>
            </div>

            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Settings</button>
        </form>
    </div>
    @endif

    {{-- Users Tab --}}
    @if($tab === 'users')
    <div class="p-6">
        {{-- Filters --}}
        <form method="GET" action="{{ route('settings.index') }}" class="flex gap-3 items-end flex-wrap mb-5">
            <input type="hidden" name="tab" value="users">
            <div class="m-0 min-w-[200px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="search">Search</label>
                <input type="text" name="search" id="search" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       value="{{ $search }}" placeholder="Name or email...">
            </div>
            <div class="m-0 min-w-[160px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="role">Role</label>
                <select name="role" id="role" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">All Roles</option>
                    @foreach(\App\Enums\Permission::allRoles() as $role)
                        @if($role === 'student') @continue @endif
                        <option value="{{ $role }}" {{ $roleFilter === $role ? 'selected' : '' }}>
                            {{ \App\Enums\Permission::roleLabel($role) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Filter</button>
            <a href="{{ route('settings.index', ['tab' => 'users']) }}" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Reset</a>
        </form>

        {{-- Users Table --}}
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[700px]">
                <thead>
                    <tr>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Email</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Role</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Status</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Joined</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="hover:bg-blue-50/50 transition-colors">
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <strong>{{ $user->name }}</strong>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle text-sm text-gray-400">{{ $user->email }}</td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 capitalize">
                                {{ $user->rolesLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle text-sm">{{ $user->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle text-sm text-gray-400">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 py-8 px-4">
                            No users found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->hasPages())
        <div class="py-4">
            {{ $users->links() }}
        </div>
        @endif
    </div>
    @endif

    {{-- HR Signatories Tab --}}
    @if($tab === 'signatures')
    <div class="p-6">
        <div class="mb-5">
            <h2 class="text-lg font-bold text-slate-900">HR Signatories</h2>
            <p class="text-sm text-gray-500 mt-1">Upload an HR officer's e-signature and mark exactly one as the active signatory. The active signatory's name, title, and signature image are rendered on all generated reports.</p>
        </div>

        @if($activeSignatory)
            <div class="mb-6 p-4 bg-blue-50/60 border border-blue-200 rounded-xl flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-600 text-white grid place-items-center font-bold shrink-0">
                    {{ strtoupper(substr($activeSignatory->user->name, 0, 1)) }}
                </div>
                <div class="flex-1">
                    <div class="text-xs uppercase tracking-wider text-blue-700 font-bold">Active signatory</div>
                    <div class="font-bold text-slate-900">{{ $activeSignatory->user->name }}</div>
                    <div class="text-sm text-slate-600">{{ $activeSignatory->title }}</div>
                </div>
                @if($activeSignatory->signature_path)
                    <img src="{{ asset('storage/' . $activeSignatory->signature_path) }}" alt="Signature"
                         class="max-h-16 max-w-[180px] object-contain bg-white rounded-lg p-2 border border-blue-100">
                @endif
            </div>
        @else
            <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-800">
                No active signatory selected. Upload a signature image and mark one HR officer as the signatory.
            </div>
        @endif

        @if($hrUsers->isEmpty())
            <div class="p-8 text-center text-gray-500 border border-dashed border-gray-300 rounded-xl">
                No active HR users found. Add a user with the <strong>Human Resource</strong> role first.
            </div>
        @else
            <div class="space-y-4">
                @foreach($hrUsers as $hr)
                    @php $sig = $signatures->get($hr->id); @endphp
                    <div class="border border-gray-200 rounded-2xl p-5 {{ $sig?->is_signatory ? 'ring-2 ring-blue-500 bg-blue-50/40' : 'bg-white' }}">
                        <div class="flex items-start gap-4 flex-wrap">
                            <div class="w-12 h-12 rounded-full bg-slate-700 text-white grid place-items-center font-bold shrink-0">
                                {{ strtoupper(substr($hr->name, 0, 1)) }}
                            </div>
                            <div class="flex-1 min-w-[200px]">
                                <div class="font-bold text-slate-900">{{ $hr->name }}</div>
                                <div class="text-sm text-gray-500">{{ $hr->email }}</div>
                                @if($sig?->is_signatory)
                                    <span class="inline-flex items-center mt-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-600 text-white">Active signatory</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 flex-wrap">
                                @if($sig && $sig->signature_path)
                                    @if(! $sig->is_signatory)
                                        <form method="POST" action="{{ route('settings.signatures.mark', $sig) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-blue-700 transition shadow-sm">Mark as signatory</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('settings.signatures.clear', $sig) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-2 bg-amber-100 text-amber-800 px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-amber-200 transition">Unmark</button>
                                        </form>
                                    @endif
                                @endif
                                @if($sig)
                                    <form method="POST" action="{{ route('settings.signatures.remove', $sig) }}"
                                          onsubmit="event.preventDefault(); showConfirm('Remove this HR signature?', this, {confirmText: 'Remove'})">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-2 bg-red-50 text-red-700 px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-red-100 transition">Remove</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <div class="mt-4 grid md:grid-cols-2 gap-5" x-data="{ mode: 'draw' }">
                            <div>
                                {{-- Mode toggle --}}
                                <div class="inline-flex items-center gap-1 p-1 rounded-lg bg-gray-100 mb-3">
                                    <button type="button" @click="mode = 'draw'"
                                            :class="mode === 'draw' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-3 py-1.5 rounded-md text-xs font-semibold transition">
                                        ✍ Draw
                                    </button>
                                    <button type="button" @click="mode = 'upload'"
                                            :class="mode === 'upload' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900'"
                                            class="px-3 py-1.5 rounded-md text-xs font-semibold transition">
                                        ⬆ Upload
                                    </button>
                                </div>

                                {{-- Draw form --}}
                                <form method="POST" action="{{ route('settings.signatures.draw', $hr) }}"
                                      x-show="mode === 'draw'" x-cloak class="space-y-2.5"
                                      data-sigpad-form="{{ $hr->id }}"
                                      onsubmit="return prepareSignaturePad({{ $hr->id }}, event)">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1" for="draw-title-{{ $hr->id }}">Signatory title</label>
                                        <input type="text" name="title" id="draw-title-{{ $hr->id }}"
                                               value="{{ old('title', $sig?->title ?? 'Head, Human Resource') }}"
                                               maxlength="191"
                                               class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1">Sign below</label>
                                        <div class="relative bg-white border border-gray-200 rounded-xl overflow-hidden" style="touch-action: none;">
                                            <canvas id="sigpad-{{ $hr->id }}" data-sigpad="{{ $hr->id }}"
                                                    style="width:100%;height:160px;display:block;cursor:crosshair;"></canvas>
                                            <div class="absolute bottom-1 left-2 text-[10px] text-gray-300 pointer-events-none select-none">x ___________________________</div>
                                        </div>
                                        <input type="hidden" name="signature_data" id="sigdata-{{ $hr->id }}">
                                        <div class="text-[11px] text-gray-400 mt-1">Sign with your mouse, finger, or stylus.</div>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" onclick="clearSignaturePad({{ $hr->id }})"
                                                class="inline-flex items-center gap-2 bg-gray-100 text-slate-700 px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-gray-200 transition">
                                            Clear
                                        </button>
                                        <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-blue-700 transition shadow-sm">
                                            {{ $sig?->signature_path ? 'Replace with drawing' : 'Save drawn signature' }}
                                        </button>
                                    </div>
                                </form>

                                {{-- Upload form --}}
                                <form method="POST" action="{{ route('settings.signatures.upload', $hr) }}" enctype="multipart/form-data"
                                      x-show="mode === 'upload'" x-cloak class="space-y-2.5">
                                    @csrf
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1" for="title-{{ $hr->id }}">Signatory title</label>
                                        <input type="text" name="title" id="title-{{ $hr->id }}"
                                               value="{{ old('title', $sig?->title ?? 'Head, Human Resource') }}"
                                               maxlength="191"
                                               class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-slate-600 mb-1" for="signature-{{ $hr->id }}">Signature image</label>
                                        <input type="file" name="signature" id="signature-{{ $hr->id }}" required
                                               accept="image/png,image/jpeg,image/webp"
                                               class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                                        <div class="text-[11px] text-gray-400 mt-1">PNG, JPG, or WebP. Max 2MB. Transparent PNG recommended.</div>
                                    </div>
                                    <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-3.5 py-2 rounded-xl text-xs font-semibold hover:bg-blue-700 transition shadow-sm">
                                        {{ $sig?->signature_path ? 'Replace signature' : 'Upload signature' }}
                                    </button>
                                </form>
                            </div>

                            {{-- Preview --}}
                            <div>
                                <div class="block text-xs font-semibold text-slate-600 mb-1">Current signature</div>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex items-center justify-center min-h-[160px]">
                                    @if($sig && $sig->signature_path)
                                        <img src="{{ asset('storage/' . $sig->signature_path) }}" alt="Signature preview"
                                             class="max-h-32 max-w-full object-contain">
                                    @else
                                        <span class="text-xs text-gray-400">No signature on file yet</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif
</div>

@if($tab === 'signatures')
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<style>[x-cloak]{display:none!important;}</style>
<script>
(function () {
    var pads = {};

    function resizeCanvas(canvas) {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        var ctx = canvas.getContext('2d');
        ctx.scale(ratio, ratio);
    }

    function initAll() {
        document.querySelectorAll('canvas[data-sigpad]').forEach(function (canvas) {
            var id = canvas.dataset.sigpad;
            if (pads[id]) return;
            // Wait until the canvas is visible (Alpine x-show may have it hidden)
            var attempt = function () {
                if (canvas.offsetWidth === 0) { return setTimeout(attempt, 100); }
                resizeCanvas(canvas);
                pads[id] = new SignaturePad(canvas, {
                    backgroundColor: 'rgba(255,255,255,0)',
                    penColor: '#0f172a',
                    minWidth: 0.8,
                    maxWidth: 2.2,
                });
            };
            attempt();
        });
    }

    window.addEventListener('resize', function () {
        Object.keys(pads).forEach(function (id) {
            var canvas = document.querySelector('canvas[data-sigpad="' + id + '"]');
            if (!canvas || canvas.offsetWidth === 0) return;
            var data = pads[id].toData();
            resizeCanvas(canvas);
            pads[id].fromData(data);
        });
    });

    document.addEventListener('DOMContentLoaded', initAll);
    document.addEventListener('turbo:load', initAll);
    // Re-init when user toggles to "Draw" mode (canvas may have been zero-width)
    document.addEventListener('click', function (e) {
        if (e.target && e.target.textContent && e.target.textContent.indexOf('Draw') !== -1) {
            setTimeout(initAll, 50);
        }
    });

    window.clearSignaturePad = function (id) {
        if (pads[id]) pads[id].clear();
    };

    window.prepareSignaturePad = function (id, event) {
        var pad = pads[id];
        if (!pad || pad.isEmpty()) {
            event.preventDefault();
            alert('Please sign before saving.');
            return false;
        }
        document.getElementById('sigdata-' + id).value = pad.toDataURL('image/png');
        return true;
    };
})();
</script>
@endpush
@endif
@endsection
