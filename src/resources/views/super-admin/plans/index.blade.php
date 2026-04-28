@extends('super-admin.layout', [
    'title' => 'Plans & activation codes',
    'subtitle' => 'Plan distribution + outstanding codes',
])

@section('content')
{{-- Plan cards --}}
<div class="grid md:grid-cols-3 gap-4 mb-8">
    @foreach ($plans as $slug => $plan)
        @php $count = $counts[$slug] ?? 0; @endphp
        <a href="{{ route('admin.tenants.index', ['plan' => $slug]) }}"
           class="group relative bg-white rounded-xl ring-1 ring-slate-200 hover:ring-brand-300 hover:shadow-md p-6 transition {{ $plan['highlight'] ? 'border-2 border-brand-600' : '' }}">
            @if ($plan['highlight'])
                <span class="absolute -top-2.5 left-6 inline-flex items-center rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white">Popular</span>
            @endif
            <div class="flex items-baseline justify-between mb-1">
                <h3 class="font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                <span class="text-2xl font-bold text-slate-900">{{ $count }}</span>
            </div>
            <p class="text-xs text-slate-500 mb-4 leading-relaxed">{{ $plan['tagline'] }}</p>
            <div class="flex items-center justify-between text-xs">
                <span class="text-slate-700 font-medium">
                    @if (is_numeric($plan['price'])) ${{ $plan['price'] }} {{ $plan['period'] }} @else {{ $plan['price'] }} @endif
                </span>
                <span class="text-brand-700 group-hover:text-brand-900 font-medium inline-flex items-center gap-0.5">
                    {{ $count === 1 ? '1 school' : "{$count} schools" }}
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
            </div>
        </a>
    @endforeach
</div>

{{-- Codes table --}}
<div class="bg-white rounded-xl ring-1 ring-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-slate-900">Activation codes</h2>
        <div class="flex flex-wrap items-center gap-1 text-xs">
            @foreach (['all' => 'Active', 'unredeemed' => 'Unredeemed', 'redeemed' => 'Redeemed', 'revoked' => 'Revoked', 'expired' => 'Expired'] as $key => $label)
                <a href="{{ route('admin.plans.index', ['status' => $key]) }}"
                   class="px-2.5 py-1 rounded-md font-medium transition {{ $statusFilter === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                <tr>
                    <th class="px-6 py-3">School</th>
                    <th class="px-6 py-3">Code</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Generated</th>
                    <th class="px-6 py-3">Expires</th>
                    <th class="px-6 py-3 text-right"></th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-slate-100 text-sm text-slate-700">
                @forelse ($codes as $code)
                    @php
                        $statusColor = match($code->status) {
                            'unredeemed' => 'bg-amber-50 text-amber-700 ring-amber-200',
                            'redeemed'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'revoked'    => 'bg-slate-100 text-slate-700 ring-slate-200',
                            'expired'    => 'bg-rose-50 text-rose-700 ring-rose-200',
                            default      => 'bg-slate-100 text-slate-700 ring-slate-200',
                        };
                    @endphp
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="px-6 py-3">
                            <a href="{{ route('admin.tenants.show', $code->tenant) }}" class="font-medium text-slate-900 hover:text-brand-700">{{ $code->tenant->name }}</a>
                        </td>
                        <td class="px-6 py-3">
                            <code class="font-mono text-xs select-all">{{ $code->code }}</code>
                        </td>
                        <td class="px-6 py-3 text-xs">{{ config('plans.' . $code->plan . '.name', $code->plan) }}</td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 {{ $statusColor }}">{{ $code->status }}</span>
                        </td>
                        <td class="px-6 py-3 text-xs text-slate-500">{{ $code->created_at->diffForHumans() }}</td>
                        <td class="px-6 py-3 text-xs text-slate-500">
                            @if ($code->status === 'unredeemed')
                                in {{ now()->diffInDays($code->expires_at) }}d
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            @if ($code->status === 'unredeemed')
                                <form method="POST" action="{{ route('admin.tenants.codes.revoke', [$code->tenant, $code]) }}" class="inline" onsubmit="return confirm('Revoke this code?');">
                                    @csrf
                                    <button class="text-xs font-medium text-rose-700 hover:text-rose-900">Revoke</button>
                                </form>
                            @elseif (in_array($code->status, ['revoked', 'expired'], true))
                                <form method="POST" action="{{ route('admin.tenants.codes.regenerate', $code->tenant) }}" class="inline">
                                    @csrf
                                    <button class="text-xs font-medium text-brand-700 hover:text-brand-900">Regenerate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No codes match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
