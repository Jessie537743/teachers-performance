@extends('super-admin.layout', [
    'title' => 'Schools',
    'subtitle' => 'All provisioned tenants across the platform',
])

@section('content')
{{-- Flash messages --}}
@if (session('status'))
    <div class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700">
        {{ session('status') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-xl bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-700">
        {{ session('error') }}
    </div>
@endif

{{-- Zombie cleanup banner: only renders when there's something to clean. --}}
@if (($zombieCount ?? 0) > 0)
<div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3.5 flex items-start gap-3 flex-wrap">
    <div class="flex-1 min-w-[260px]">
        <div class="flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            <strong class="text-amber-900">Zombie tenants detected</strong>
        </div>
        <p class="text-sm text-amber-800 mt-1">
            <strong>{{ $zombieCount }}</strong> tenants are stuck in
            <code class="bg-white border border-amber-200 rounded px-1.5 py-0.5 text-xs">provisioning</code>,
            <code class="bg-white border border-amber-200 rounded px-1.5 py-0.5 text-xs">failed</code>, or
            <code class="bg-white border border-amber-200 rounded px-1.5 py-0.5 text-xs">pending_activation</code>,
            older than 7 days, with no redeemed activation code. Almost certainly abandoned signups or bot submissions.
        </p>
        <p class="text-xs text-amber-700 mt-1">This will cascade-delete each tenant's child rows and drop the per-tenant MySQL database. <strong>Cannot be undone.</strong></p>
    </div>
    <button type="button"
        onclick="document.getElementById('purgeZombiesModal').classList.remove('hidden')"
        class="inline-flex items-center gap-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold px-4 py-2.5">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
        Purge zombies
    </button>
</div>

{{-- Confirmation modal (hidden by default). Form posts to purge-zombies. --}}
<div id="purgeZombiesModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md ring-1 ring-slate-200">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center gap-3">
            <span class="w-9 h-9 rounded-full bg-rose-100 text-rose-600 grid place-items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
            </span>
            <div>
                <h3 class="font-bold text-slate-900">Confirm zombie purge</h3>
                <p class="text-xs text-slate-500">This removes {{ $zombieCount }} tenants and their databases.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.tenants.purge-zombies') }}" class="p-5 space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">Minimum age (days)</label>
                <input type="number" name="min_age_days" value="7" min="1" max="365"
                    class="w-32 rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500/30">
                <p class="text-xs text-slate-500 mt-1">Only tenants created more than this many days ago are eligible.</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wider">
                    Type <span class="font-mono text-rose-600">PURGE</span> to confirm
                </label>
                <input type="text" name="confirm" autocomplete="off" required
                    placeholder="PURGE"
                    class="w-full rounded-lg border-slate-300 font-mono text-sm focus:border-rose-500 focus:ring-rose-500/30">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button"
                    onclick="document.getElementById('purgeZombiesModal').classList.add('hidden')"
                    class="px-4 py-2 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-100">
                    Cancel
                </button>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-sm font-semibold">
                    Yes, purge {{ $zombieCount }} tenants
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- KPI cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3 mb-8">
    @php
        $kpis = [
            ['label' => 'Total schools',     'value' => $stats['total'],               'tone' => 'slate',   'href' => route('admin.tenants.index')],
            ['label' => 'Active',            'value' => $stats['active'],              'tone' => 'emerald', 'href' => route('admin.tenants.index', ['status' => 'active'])],
            ['label' => 'Awaiting act.',     'value' => $stats['awaiting_activation'] ?? 0, 'tone' => 'amber', 'href' => route('admin.tenants.index', ['status' => 'awaiting_activation'])],
            ['label' => 'Pending',           'value' => $stats['pending_activation'],  'tone' => 'amber',   'href' => route('admin.tenants.index', ['status' => 'pending_activation'])],
            ['label' => 'Awaiting pay',      'value' => $stats['awaiting_payment'],    'tone' => 'indigo',  'href' => route('admin.tenants.index', ['status' => 'awaiting_payment'])],
            ['label' => 'Suspended',         'value' => $stats['suspended'],           'tone' => 'slate',   'href' => route('admin.tenants.index', ['status' => 'suspended'])],
            ['label' => 'Failed',            'value' => $stats['failed'],              'tone' => 'rose',    'href' => route('admin.tenants.index', ['status' => 'failed'])],
        ];
        $tones = [
            'slate'   => ['ring' => 'ring-slate-200',   'icon' => 'bg-slate-100   text-slate-700'],
            'emerald' => ['ring' => 'ring-emerald-200', 'icon' => 'bg-emerald-100 text-emerald-700'],
            'amber'   => ['ring' => 'ring-amber-200',   'icon' => 'bg-amber-100   text-amber-700'],
            'indigo'  => ['ring' => 'ring-indigo-200',  'icon' => 'bg-indigo-100  text-indigo-700'],
            'rose'    => ['ring' => 'ring-rose-200',    'icon' => 'bg-rose-100    text-rose-700'],
        ];
    @endphp

    @foreach ($kpis as $kpi)
        <a href="{{ $kpi['href'] }}" class="rounded-xl bg-white px-4 py-3.5 shadow-sm ring-1 {{ $tones[$kpi['tone']]['ring'] }} hover:shadow-md transition">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $kpi['label'] }}</div>
            <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ $kpi['value'] }}</div>
        </a>
    @endforeach
</div>

{{-- Filter bar --}}
<div class="bg-white rounded-xl ring-1 ring-slate-200 mb-4">
    <form method="GET" action="{{ route('admin.tenants.index') }}" class="p-4 flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[220px]">
            <span class="absolute inset-y-0 left-3 flex items-center text-slate-400 pointer-events-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/></svg>
            </span>
            <input type="search" name="q" value="{{ $search }}" placeholder="Search by name or subdomain…"
                   class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none">
        </div>

        <select name="plan" class="rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500/30">
            <option value="">All plans</option>
            @foreach (config('plans') as $slug => $plan)
                <option value="{{ $slug }}" @selected($planFilter === $slug)>{{ $plan['name'] }} ({{ $planCounts[$slug] ?? 0 }})</option>
            @endforeach
        </select>

        <select name="status" class="rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-brand-500/30">
            <option value="">All statuses</option>
            @foreach (['active', 'awaiting_activation', 'pending_activation', 'awaiting_payment', 'provisioning', 'suspended', 'failed'] as $s)
                <option value="{{ $s }}" @selected($statusFilter === $s)>{{ str_replace('_', ' ', $s) }}</option>
            @endforeach
        </select>

        <button type="submit" class="inline-flex items-center rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-medium px-4 py-2.5">Apply</button>

        @if ($planFilter || $statusFilter || $search)
            <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-500 hover:text-slate-700">Clear</a>
        @endif
    </form>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl ring-1 ring-slate-200 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-6 py-3">School</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Created</th>
                    <th class="px-6 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100 text-sm text-slate-700">
                @forelse ($tenants as $tenant)
                    @php
                        $planColor = match($tenant->plan) {
                            'free'       => 'bg-slate-100 text-slate-700 ring-slate-200',
                            'pro'        => 'bg-brand-50 text-brand-700 ring-brand-200',
                            'enterprise' => 'bg-purple-50 text-purple-700 ring-purple-200',
                            default      => 'bg-slate-100 text-slate-700 ring-slate-200',
                        };
                        $statusColor = match($tenant->status) {
                            'active'              => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'provisioning'        => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'pending_activation'  => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'awaiting_activation' => 'bg-yellow-50 text-yellow-700 ring-yellow-200',
                            'awaiting_payment'    => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                            'suspended'           => 'bg-slate-100 text-slate-700 ring-slate-200',
                            'failed'              => 'bg-rose-50 text-rose-700 ring-rose-200',
                            default               => 'bg-slate-100 text-slate-700 ring-slate-200',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-6 py-3.5">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-white grid place-items-center text-xs font-bold uppercase shrink-0">
                                    {{ substr($tenant->name, 0, 2) }}
                                </div>
                                <div class="min-w-0">
                                    <div class="font-semibold text-slate-900 truncate">{{ $tenant->name }}</div>
                                    <code class="text-[11px] text-slate-500 font-mono">{{ $tenant->subdomain }}.{{ str_replace('admin.', '', env('APP_ADMIN_DOMAIN', 'localhost')) }}</code>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $planColor }}">
                                {{ config('plans.' . $tenant->plan . '.name', $tenant->plan ?: 'Unassigned') }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5">
                            <span class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusColor }}">
                                <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
                                {{ str_replace('_', ' ', $tenant->status) }}
                            </span>
                        </td>
                        <td class="px-6 py-3.5 text-slate-500 text-xs whitespace-nowrap">
                            {{ $tenant->created_at?->diffForHumans() }}
                        </td>
                        <td class="px-6 py-3.5 text-right">
                            <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex items-center gap-1 text-sm font-medium text-brand-700 hover:text-brand-900">
                                View
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="mx-auto w-12 h-12 rounded-full bg-slate-100 grid place-items-center text-slate-400 mb-3">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg>
                            </div>
                            <p class="text-sm font-medium text-slate-700">No schools match the current filters.</p>
                            <p class="text-xs text-slate-500 mt-1">Try clearing filters or <a href="{{ route('admin.tenants.create') }}" class="text-brand-700 hover:underline">provision one now</a>.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($tenants->hasPages())
        <div class="px-6 py-3 border-t border-slate-100 text-xs text-slate-600">
            {{ $tenants->links() }}
        </div>
    @endif
</div>
@endsection
