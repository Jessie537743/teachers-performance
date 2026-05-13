@extends('layouts.app')
@section('title', 'API Integration')
@section('page-title', 'API Integration')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">API Integration</h1>
        <p class="text-sm text-gray-500 mt-1">Pull <strong>Students</strong>, <strong>Departments</strong>, <strong>Subjects</strong>, and <strong>Courses</strong> from an external system on demand.</p>
    </div>
    @if($integration)
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $integration->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-700' }}">
            {{ $integration->is_active ? 'Active' : 'Inactive' }}
        </span>
    @endif
</div>

{{-- Flash messages --}}
@if(session('success'))
    <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
        <strong>Please fix the following:</strong>
        <ul class="list-disc list-inside mt-1">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5">
    {{-- =============== Left column: configuration form =============== --}}
    <div class="lg:col-span-2 bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
        <div class="px-5 py-3.5 border-b border-gray-200">
            <span class="font-bold text-slate-900">Configuration</span>
            <p class="text-xs text-gray-500 mt-1">Save first, then test the connection, then run syncs from the right panel.</p>
        </div>

        <form method="POST" action="{{ route('api-integration.update') }}" class="p-5 space-y-5">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Integration name</label>
                    <input type="text" name="name" value="{{ old('name', $integration->name ?? 'External System') }}"
                        class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <p class="text-xs text-gray-500 mt-1">Friendly label shown in logs (e.g. "SIS", "Registrar API").</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Active</label>
                    <label class="inline-flex items-center gap-2 mt-2">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $integration->is_active ?? false) ? 'checked' : '' }}
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-slate-700">Allow syncs from this integration</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Inactive integrations refuse Sync Now even when configured.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Base URL</label>
                <input type="url" name="base_url" placeholder="https://sis.school.edu/api/v1"
                    value="{{ old('base_url', $integration->base_url ?? '') }}"
                    class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                <p class="text-xs text-gray-500 mt-1">Each resource path below is appended to this URL.</p>
            </div>

            @php
                $currentMode = old('auth_mode', $integration->auth_mode ?? \App\Models\ApiIntegration::AUTH_API_KEY);
            @endphp

            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Auth mode</label>
                <select name="auth_mode" id="auth_mode"
                    onchange="document.getElementById('authBlocks').setAttribute('data-mode', this.value)"
                    class="w-full md:w-80 border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    @foreach(\App\Models\ApiIntegration::authModes() as $value => $label)
                        <option value="{{ $value }}" {{ $currentMode === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">Pick the scheme the external API expects.</p>
            </div>

            <div id="authBlocks" data-mode="{{ $currentMode }}" class="space-y-4">

                {{-- ============ Block: API Key (single header) ============ --}}
                <div data-block="api_key" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">API key</label>
                        <input type="password" name="api_key" autocomplete="off"
                            placeholder="{{ $integration && $integration->api_key ? '•••••••• (leave blank to keep current)' : 'paste your API key' }}"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">Stored encrypted. Leave blank when re-saving to keep the existing key.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Header name</label>
                        <input type="text" name="header_name" value="{{ old('header_name', $integration->header_name ?? 'Authorization') }}"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">e.g. <code>Authorization</code> or <code>X-API-Key</code></p>
                    </div>
                    <div class="md:col-start-3">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Header prefix</label>
                        <input type="text" name="header_prefix" value="{{ old('header_prefix', $integration->header_prefix ?? 'Bearer ') }}"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">e.g. <code>Bearer </code> (with trailing space) or leave blank. Ignored in Key + Secret / Basic modes.</p>
                    </div>
                </div>

                {{-- ============ Block: Key + Secret (two headers) — secret + secret-header ============ --}}
                {{-- Note: the api_key input above is shared with this mode; only the SECRET field is gated. --}}
                <div data-block="key_and_secret" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">API secret</label>
                        <input type="password" name="api_secret" autocomplete="off"
                            placeholder="{{ $integration && $integration->api_secret ? '•••••••• (leave blank to keep current)' : 'paste your API secret' }}"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">Encrypted at rest. Sent in its own header below (or used as Basic Auth password).</p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Secret header name</label>
                        <input type="text" name="secret_header_name" value="{{ old('secret_header_name', $integration->secret_header_name ?? 'X-API-Secret') }}"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 font-mono text-sm">
                        <p class="text-xs text-gray-500 mt-1">e.g. <code>X-API-Secret</code>. Ignored in Basic Auth mode.</p>
                    </div>
                </div>

                {{-- Mode-specific explanation strip --}}
                <div data-block="explainer" class="text-xs text-gray-500 bg-gray-50 border border-gray-200 rounded-lg p-3">
                    <div data-mode-text="api_key">
                        Sends a single header: <code class="bg-white border border-gray-200 rounded px-1.5 py-0.5">{{ $integration->header_name ?? 'Authorization' }}: {{ $integration->header_prefix ?? 'Bearer ' }}&lt;api_key&gt;</code>
                    </div>
                    <div data-mode-text="key_and_secret">
                        Sends two headers: <code class="bg-white border border-gray-200 rounded px-1.5 py-0.5">{{ $integration->header_name ?? 'X-API-Key' }}: &lt;api_key&gt;</code> and <code class="bg-white border border-gray-200 rounded px-1.5 py-0.5">{{ $integration->secret_header_name ?? 'X-API-Secret' }}: &lt;api_secret&gt;</code>
                    </div>
                    <div data-mode-text="basic">
                        Sends <code class="bg-white border border-gray-200 rounded px-1.5 py-0.5">Authorization: Basic base64(&lt;api_key&gt;:&lt;api_secret&gt;)</code> — HTTP Basic Auth with the key as username and the secret as password.
                    </div>
                </div>
            </div>

            <style>
                /* CSS-only visibility based on the wrapper's data-mode. */
                #authBlocks[data-mode="api_key"]        [data-block="key_and_secret"] { display: none; }
                #authBlocks[data-mode="key_and_secret"] [data-block="api_key"] .md\:col-start-3 { display: none; } /* hide Header prefix */
                #authBlocks[data-mode="basic"]          [data-block="api_key"] .md\:col-start-3 { display: none; } /* hide Header prefix */
                #authBlocks[data-mode="basic"]          [data-block="api_key"] > div:nth-child(2) { display: none; } /* hide Header name */
                #authBlocks[data-mode="basic"]          [data-block="key_and_secret"] > div:last-child { display: none; } /* hide secret-header name */
                /* Explainer one-of: hide non-matching texts */
                #authBlocks[data-mode="api_key"]        [data-mode-text]:not([data-mode-text="api_key"]) { display: none; }
                #authBlocks[data-mode="key_and_secret"] [data-mode-text]:not([data-mode-text="key_and_secret"]) { display: none; }
                #authBlocks[data-mode="basic"]          [data-mode-text]:not([data-mode-text="basic"]) { display: none; }
            </style>

            <div class="border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="font-semibold text-slate-700">Resource paths</span>
                    <span class="text-xs text-gray-500">Relative to base URL · leave blank to disable a resource</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    @php $paths = $integration?->resource_paths ?? []; @endphp
                    @foreach(['departments' => '/departments', 'courses' => '/courses', 'subjects' => '/subjects', 'students' => '/students'] as $res => $placeholder)
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1 uppercase tracking-wider">{{ $res }}</label>
                            <input type="text" name="paths[{{ $res }}]" placeholder="{{ $placeholder }}"
                                value="{{ old('paths.' . $res, $paths[$res] ?? '') }}"
                                class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 font-mono text-sm">
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="flex items-center justify-between border-t border-gray-200 pt-4">
                <div class="flex gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">
                        Save
                    </button>
                </div>
                @if($integration)
                    <form method="POST" action="{{ route('api-integration.destroy') }}" onsubmit="return confirm('Remove this API integration? Existing imported rows are kept; only the connection is deleted.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">Remove integration</button>
                    </form>
                @endif
            </div>
        </form>
    </div>

    {{-- =============== Right column: actions + status =============== --}}
    <div class="space-y-5">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-200">
                <span class="font-bold text-slate-900">Test &amp; Sync</span>
            </div>
            <div class="p-5 space-y-3">
                @if(!$integration)
                    <p class="text-sm text-gray-500">Save your configuration first to enable test and sync.</p>
                @else
                    <form method="POST" action="{{ route('api-integration.test') }}">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 bg-slate-100 text-slate-800 px-4 py-2.5 rounded-xl font-semibold hover:bg-slate-200 transition">
                            Test connection
                        </button>
                    </form>
                    <div class="border-t border-gray-200 pt-3 space-y-2">
                        @foreach(['departments', 'courses', 'subjects', 'students'] as $res)
                            @php $hasPath = !empty(($integration->resource_paths ?? [])[$res] ?? null); @endphp
                            <form method="POST" action="{{ route('api-integration.sync', $res) }}">
                                @csrf
                                <button type="submit"
                                    {{ (!$hasPath || !$integration->is_active) ? 'disabled' : '' }}
                                    class="w-full inline-flex items-center justify-between gap-2 px-4 py-2 rounded-xl font-semibold text-sm transition
                                        {{ ($hasPath && $integration->is_active)
                                            ? 'bg-blue-600 text-white hover:bg-blue-700'
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed' }}">
                                    <span>Sync {{ ucfirst($res) }}</span>
                                    <span class="text-xs opacity-80">{{ $hasPath ? '→' : 'no path' }}</span>
                                </button>
                            </form>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        @if($integration && $integration->last_synced_at)
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-3.5 border-b border-gray-200">
                    <span class="font-bold text-slate-900">Last sync</span>
                </div>
                <div class="p-5 text-sm space-y-1.5">
                    <div class="flex justify-between"><span class="text-gray-500">Resource</span> <span class="font-semibold">{{ $integration->last_sync_resource ?? '—' }}</span></div>
                    <div class="flex justify-between"><span class="text-gray-500">When</span> <span>{{ $integration->last_synced_at->diffForHumans() }}</span></div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Status</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold {{ $integration->last_sync_status === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ ucfirst($integration->last_sync_status ?? '—') }}
                        </span>
                    </div>
                    @php $stats = $integration->last_sync_stats ?? []; @endphp
                    @if($stats)
                        <div class="border-t border-gray-100 pt-2 mt-2 grid grid-cols-2 gap-1 text-xs">
                            <span class="text-gray-500">Created</span><span class="text-right font-semibold">{{ $stats['created'] ?? 0 }}</span>
                            <span class="text-gray-500">Updated</span><span class="text-right font-semibold">{{ $stats['updated'] ?? 0 }}</span>
                            <span class="text-gray-500">Skipped</span><span class="text-right font-semibold">{{ $stats['skipped'] ?? 0 }}</span>
                            <span class="text-gray-500">Errors</span><span class="text-right font-semibold {{ ($stats['errors'] ?? 0) > 0 ? 'text-red-600' : '' }}">{{ $stats['errors'] ?? 0 }}</span>
                            <span class="text-gray-500">Total</span><span class="text-right font-semibold">{{ $stats['total'] ?? 0 }}</span>
                        </div>
                    @endif
                    @if($integration->last_sync_error)
                        <div class="mt-2 text-xs text-red-600 bg-red-50 border border-red-100 rounded-lg p-2 break-words">
                            {{ $integration->last_sync_error }}
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Expected payload reference --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-200">
        <span class="font-bold text-slate-900">Expected response format</span>
    </div>
    <div class="p-5 text-sm text-gray-700 space-y-3">
        <p>Each resource endpoint should return either a plain JSON array, or an object with a <code class="bg-gray-100 px-1 rounded">data</code> array. Each row needs a stable <code class="bg-gray-100 px-1 rounded">id</code> (used as <code class="bg-gray-100 px-1 rounded">external_id</code> on our side for upserts).</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Departments</div>
                <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs overflow-x-auto"><code>[
  {
    "id": "DEPT-001",
    "code": "CCIS",
    "name": "College of Computing &amp; IS",
    "type": "teaching"
  }
]</code></pre>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Courses</div>
                <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs overflow-x-auto"><code>[
  {
    "id": "CRS-100",
    "code": "BSIT",
    "name": "BS Information Technology",
    "department_id": "DEPT-001",
    "year_levels": "1-4",
    "semester": "2nd",
    "school_year": "2025-2026"
  }
]</code></pre>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Subjects</div>
                <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs overflow-x-auto"><code>[
  {
    "id": "SUB-500",
    "code": "IT-101",
    "title": "Intro to Programming",
    "department_id": "DEPT-001",
    "course": "BSIT",
    "year_level": "1",
    "section": "1",
    "semester": "2nd",
    "school_year": "2025-2026"
  }
]</code></pre>
            </div>
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-slate-600 mb-1">Students</div>
                <pre class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-xs overflow-x-auto"><code>[
  {
    "id": "STU-9001",
    "email": "jane.doe@school.edu",
    "name": "Jane Doe",
    "department_id": "DEPT-001",
    "course": "BSIT",
    "year_level": "1",
    "section": "1",
    "status": "regular",
    "semester": "2nd",
    "school_year": "2025-2026"
  }
]</code></pre>
            </div>
        </div>
        <p class="text-xs text-gray-500">Conflict strategy is <strong>upsert by external id</strong>: re-syncing updates matching rows in place. New records are inserted; previously-imported rows that disappear from the feed are <em>not</em> deleted (manually deactivate if needed).</p>
    </div>
</div>
@endsection
