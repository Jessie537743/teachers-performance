@extends('super-admin.layout', [
    'title' => 'School provisioned',
    'subtitle' => $tenant->name . ' is ready to onboard',
])

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-xl ring-1 ring-slate-200 overflow-hidden">
        {{-- Success header --}}
        <div class="px-8 py-6 bg-gradient-to-br from-emerald-50 via-emerald-50 to-white border-b border-emerald-100 flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-emerald-100 grid place-items-center text-emerald-700 shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div>
                <h1 class="text-lg font-bold text-slate-900">{{ $tenant->name }} is provisioned</h1>
                <p class="text-sm text-slate-600">Database created, migrations run, template seeded.</p>
            </div>
        </div>

        <div class="p-8 space-y-6">
            {{-- Activation code (banner) --}}
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-2">Activation code (shown once)</div>
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-5 text-center">
                    <div class="font-mono text-3xl tracking-[0.3em] text-slate-900 select-all">{{ $activationCode->code }}</div>
                </div>
                <button type="button"
                        onclick="navigator.clipboard.writeText('{{ $activationCode->code }}'); this.innerText='Copied!'"
                        class="mt-2 inline-flex items-center gap-1.5 text-xs text-slate-600 hover:text-slate-900">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                    Copy code
                </button>
            </div>

            {{-- Activation URL --}}
            <div>
                <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Activation URL</div>
                <div class="flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                    <code class="font-mono text-slate-700 flex-1 truncate select-all">{{ url('/activate?code=' . $activationCode->code) }}</code>
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ url('/activate?code=' . $activationCode->code) }}'); this.innerText='Copied'"
                            class="rounded-md bg-white border border-slate-300 px-2.5 py-1 hover:bg-slate-100 shrink-0">
                        Copy
                    </button>
                </div>
            </div>

            <dl class="grid sm:grid-cols-2 gap-4 text-sm pt-2 border-t border-slate-100">
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Plan</dt>
                    <dd class="text-slate-900 font-medium mt-0.5">{{ config('plans.' . $activationCode->plan . '.name') }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Expires</dt>
                    <dd class="text-slate-900 mt-0.5">{{ $activationCode->expires_at->toDayDateTimeString() }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Intended admin</dt>
                    <dd class="text-slate-900 mt-0.5">{{ $activationCode->intended_admin_name }} &lt;{{ $activationCode->intended_admin_email }}&gt;</dd>
                </div>
            </dl>

            <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 text-xs text-slate-600 leading-relaxed">
                If they lose this code, you can revoke + regenerate from the school's detail page. Codes expire in 30 days.
            </div>
        </div>

        <div class="px-8 py-4 border-t border-slate-100 bg-slate-50 flex items-center justify-between">
            <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">&larr; Back to schools</a>
            <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 px-4 py-2 text-sm font-medium text-white">
                Open school detail
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>
    </div>
</div>
@endsection
