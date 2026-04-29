@extends('super-admin.layout', [
    'title' => $tenant->name,
    'subtitle' => $tenant->subdomain . '.' . str_replace('admin.', '', env('APP_ADMIN_DOMAIN', 'localhost')),
])

@section('content')
@php
    $statusColor = match($tenant->status) {
        'active'             => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'provisioning'       => 'bg-amber-50 text-amber-700 ring-amber-200',
        'pending_activation' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'awaiting_payment'   => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
        'suspended'          => 'bg-slate-100 text-slate-700 ring-slate-200',
        'failed'             => 'bg-rose-50 text-rose-700 ring-rose-200',
        default              => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
    $currentCode = $tenant->activationCodes->firstWhere('status', 'unredeemed');
    if ($currentCode && $currentCode->expires_at->isPast()) {
        $currentCode = null;
    }
    $latestCode = $tenant->activationCodes->first();
    $tenantUrl     = tenant_url($tenant->subdomain);
    $tenantHost    = preg_replace('#^https?://#', '', $tenantUrl);
@endphp

<a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    All schools
</a>

{{-- Header card --}}
<div class="bg-white rounded-xl ring-1 ring-slate-200 p-6 mb-6">
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div class="flex items-center gap-4 min-w-0">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-brand-500 to-brand-800 text-white grid place-items-center text-lg font-bold uppercase shrink-0">
                {{ substr($tenant->name, 0, 2) }}
            </div>
            <div class="min-w-0">
                <h1 class="text-xl font-bold text-slate-900 truncate">{{ $tenant->name }}</h1>
                <a href="{{ $tenantUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 mt-1 text-sm text-brand-700 hover:text-brand-900">
                    <span class="font-mono">{{ $tenantHost }}</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
            </div>
        </div>
        <span class="inline-flex items-center gap-1.5 rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusColor }} self-start">
            <span class="w-1.5 h-1.5 rounded-full bg-current opacity-70"></span>
            {{ str_replace('_', ' ', $tenant->status) }}
        </span>
    </div>

    <dl class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
        <div>
            <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Plan</dt>
            <dd class="text-slate-900 font-semibold mt-1">{{ config('plans.' . $tenant->plan . '.name', $tenant->plan ?: '—') }}</dd>
        </div>
        <div>
            <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Database</dt>
            <dd class="text-slate-900 font-mono text-xs mt-1 truncate">{{ $tenant->getAttribute('database') }}</dd>
        </div>
        <div>
            <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Tenant ID</dt>
            <dd class="text-slate-900 font-mono text-xs mt-1">#{{ $tenant->id }}</dd>
        </div>
        <div>
            <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Created</dt>
            <dd class="text-slate-900 mt-1">{{ $tenant->created_at?->toDayDateTimeString() }}</dd>
        </div>
    </dl>
</div>

<div class="grid lg:grid-cols-3 gap-6">
    {{-- Activation --}}
    <div class="lg:col-span-2 bg-white rounded-xl ring-1 ring-slate-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
                Activation
            </h2>
            <span class="text-[11px] text-slate-500">{{ $tenant->activationCodes->count() }} total codes</span>
        </div>

        @if ($currentCode)
            <div class="rounded-lg bg-amber-50 border border-amber-200 p-4 mb-4">
                <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-800 mb-2">Active code</div>
                <div class="font-mono text-2xl tracking-widest text-slate-900 select-all">{{ $currentCode->code }}</div>
                <div class="mt-3 text-xs text-slate-600 grid sm:grid-cols-2 gap-y-1">
                    <span>Expires <strong>{{ $currentCode->expires_at->diffForHumans() }}</strong></span>
                    <span>Intended: <code class="font-mono">{{ $currentCode->intended_admin_email }}</code></span>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <button type="button" onclick="navigator.clipboard.writeText('{{ url('/activate?code=' . $currentCode->code) }}'); this.innerText='Copied!'"
                            class="inline-flex items-center gap-1.5 rounded-md bg-white border border-amber-300 px-3 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Copy activation URL
                    </button>
                    <form method="POST" action="{{ route('admin.tenants.codes.revoke', [$tenant, $currentCode]) }}" onsubmit="return confirm('Revoke this code? It can no longer be redeemed.');">
                        @csrf
                        <button class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs hover:bg-slate-50">Revoke</button>
                    </form>
                    <form method="POST" action="{{ route('admin.tenants.codes.regenerate', $tenant) }}">
                        @csrf
                        <button class="inline-flex items-center rounded-md bg-slate-900 px-3 py-1.5 text-xs text-white hover:bg-slate-800">Revoke + regenerate</button>
                    </form>
                </div>
            </div>
        @elseif ($latestCode && $latestCode->status === 'redeemed')
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-4 text-sm text-emerald-900">
                <span class="font-semibold">Activated.</span>
                Redeemed by <code class="font-mono">{{ $latestCode->intended_admin_email }}</code>
                on {{ $latestCode->redeemed_at?->toDayDateTimeString() }}.
            </div>
        @else
            <div class="rounded-lg bg-slate-50 border border-slate-200 p-4">
                <p class="text-sm text-slate-700 mb-3">
                    @if ($latestCode)
                        Last code was <strong>{{ $latestCode->status }}</strong> ({{ $latestCode->code }}).
                    @else
                        No activation codes have been generated for this school.
                    @endif
                </p>
                <form method="POST" action="{{ route('admin.tenants.codes.regenerate', $tenant) }}">
                    @csrf
                    <button class="inline-flex items-center gap-1.5 rounded-md bg-brand-600 hover:bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Generate new code
                    </button>
                </form>
            </div>
        @endif

        {{-- Billing summary --}}
        <div class="mt-6 pt-6 border-t border-slate-100">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-brand-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                    Billing
                </h3>
                @php
                    $subColor = match($tenant->subscription_status) {
                        'active'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                        'grace'    => 'bg-amber-50 text-amber-700 ring-amber-200',
                        'canceled' => 'bg-slate-100 text-slate-700 ring-slate-200',
                        default    => 'bg-slate-100 text-slate-500 ring-slate-200',
                    };
                @endphp
                <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $subColor }}">
                    {{ str_replace('_', ' ', $tenant->subscription_status) }}
                </span>
            </div>

            @if ($tenant->subscription_status === 'none')
                <p class="text-sm text-slate-500">No subscription on file (free plan or pre-billing tenant).</p>
            @else
                <dl class="grid grid-cols-2 lg:grid-cols-4 gap-3 text-sm mb-4">
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Cycle</dt>
                        <dd class="text-slate-900 font-semibold mt-0.5 capitalize">{{ $tenant->billing_cycle ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Amount</dt>
                        <dd class="text-slate-900 font-semibold mt-0.5">
                            ${{ config('plans.' . $tenant->plan . '.prices.' . ($tenant->billing_cycle ?? 'monthly')) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Next charge</dt>
                        <dd class="text-slate-900 mt-0.5 text-xs">
                            {{ $tenant->next_charge_at?->toDayDateTimeString() ?? '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-[10px] uppercase tracking-wider text-slate-500 font-semibold">Last charged</dt>
                        <dd class="text-slate-900 mt-0.5 text-xs">
                            {{ $tenant->last_charge_at?->diffForHumans() ?? '—' }}
                        </dd>
                    </div>
                </dl>

                <div class="flex flex-wrap gap-2 mb-4">
                    @if (in_array($tenant->subscription_status, ['active', 'grace'], true))
                        <form method="POST" action="{{ route('admin.tenants.billing.charge', $tenant) }}" onsubmit="return confirm('Charge {{ $tenant->name }} now?');">
                            @csrf
                            <button class="inline-flex items-center gap-1.5 rounded-md bg-brand-600 hover:bg-brand-700 px-3 py-1.5 text-xs font-semibold text-white">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                Charge now
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.tenants.billing.cancel', $tenant) }}" onsubmit="return confirm('Cancel subscription for {{ $tenant->name }}? They keep access until period ends.');">
                            @csrf
                            <button class="inline-flex items-center rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs hover:bg-slate-50">
                                Cancel subscription
                            </button>
                        </form>
                    @endif
                </div>

                @if ($tenant->subscriptions->isNotEmpty())
                    <details class="text-xs">
                        <summary class="cursor-pointer text-slate-600 hover:text-slate-900 font-medium">
                            Invoice history ({{ $tenant->subscriptions->count() }})
                        </summary>
                        <table class="mt-3 w-full text-left">
                            <thead class="text-[10px] uppercase tracking-wider text-slate-500">
                                <tr><th class="py-1">Date</th><th>Amount</th><th>Period</th><th>Status</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ($tenant->subscriptions as $s)
                                    <tr>
                                        <td class="py-1.5 text-slate-700">{{ $s->created_at->toDateString() }}</td>
                                        <td class="text-slate-900 font-medium">{{ $s->formatted_amount }}</td>
                                        <td class="text-slate-500">{{ $s->period_start->toDateString() }} → {{ $s->period_end->toDateString() }}</td>
                                        <td>
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-semibold ring-1
                                                @class([
                                                    'bg-emerald-50 text-emerald-700 ring-emerald-200' => $s->status === 'paid',
                                                    'bg-rose-50 text-rose-700 ring-rose-200'          => $s->status === 'failed',
                                                    'bg-slate-100 text-slate-700 ring-slate-200'      => $s->status === 'refunded',
                                                ])">{{ $s->status }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </details>
                @endif
            @endif
        </div>

        @if ($tenant->activationCodes->count() > ($currentCode ? 1 : 0))
            <details class="mt-5">
                <summary class="cursor-pointer text-sm text-slate-600 hover:text-slate-900">
                    Code history ({{ $tenant->activationCodes->count() }})
                </summary>
                <ul class="mt-3 divide-y divide-slate-100 text-xs">
                    @foreach ($tenant->activationCodes as $c)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <code class="font-mono text-slate-700">{{ $c->code }}</code>
                            <span class="text-slate-500">{{ $c->created_at?->diffForHumans() }}</span>
                            <span class="inline-flex items-center rounded-md px-1.5 py-0.5 text-[10px] font-semibold ring-1
                                @class([
                                    'bg-emerald-50 text-emerald-700 ring-emerald-200' => $c->status === 'redeemed',
                                    'bg-amber-50 text-amber-700 ring-amber-200'       => $c->status === 'unredeemed',
                                    'bg-slate-100 text-slate-700 ring-slate-200'      => $c->status === 'revoked',
                                    'bg-rose-50 text-rose-700 ring-rose-200'          => $c->status === 'expired',
                                ])">{{ $c->status }}</span>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>

    {{-- Side actions --}}
    <div class="space-y-6">
        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Actions</h2>
            <div class="space-y-2">
                @if ($tenant->status === 'active')
                    <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" onsubmit="return confirm('Suspend {{ $tenant->name }}? Logins will be blocked.');">
                        @csrf
                        <button class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Suspend school
                        </button>
                    </form>
                @endif

                @if ($tenant->status === 'suspended')
                    <form method="POST" action="{{ route('admin.tenants.resume', $tenant) }}">
                        @csrf
                        <button class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 px-4 py-2 text-sm font-medium text-white">
                            Resume school
                        </button>
                    </form>
                @endif

                @if ($tenant->status === 'failed')
                    <form method="POST" action="{{ route('admin.tenants.retry', $tenant) }}">
                        @csrf
                        <button class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-slate-900 hover:bg-slate-800 px-4 py-2 text-sm font-medium text-white">Retry provisioning</button>
                    </form>
                @endif

                <a href="{{ $tenantUrl }}" target="_blank" rel="noopener" class="w-full inline-flex items-center justify-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    Open tenant site
                </a>
            </div>
        </div>

        @if ($jobs->isNotEmpty())
            <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
                <h2 class="text-sm font-semibold text-slate-900 mb-3">Provisioning history</h2>
                <ul class="space-y-3">
                    @foreach ($jobs as $job)
                        <li class="text-xs">
                            <div class="flex items-baseline justify-between">
                                <span class="font-semibold text-slate-900 capitalize">{{ $job->status }}</span>
                                <span class="text-slate-500">{{ $job->created_at?->diffForHumans() }}</span>
                            </div>
                            @if ($job->error)
                                <pre class="mt-1.5 bg-rose-50 border border-rose-200 rounded p-2 text-rose-700 whitespace-pre-wrap break-words">{{ $job->error }}</pre>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>
@endsection
