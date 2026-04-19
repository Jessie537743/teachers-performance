@extends('super-admin.layout', ['title' => 'Plans'])

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-semibold text-slate-900">Plans</h1>
    <p class="text-sm text-slate-500">Plan distribution across active schools and outstanding activation codes.</p>
</div>

<div class="grid md:grid-cols-3 gap-4 mb-8">
    @foreach ($plans as $slug => $plan)
        <div class="bg-white shadow rounded-lg p-6 {{ $plan['highlight'] ? 'border-2 border-slate-900' : '' }}">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                    <p class="text-xs text-slate-500 mt-1">{{ $plan['tagline'] }}</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-sm font-medium text-slate-700">{{ $counts[$slug] ?? 0 }}</span>
            </div>
            <p class="text-xs text-slate-500 mb-3">
                @if (is_numeric($plan['price']))
                    ${{ $plan['price'] }} {{ $plan['period'] }}
                @else
                    {{ $plan['price'] }}
                @endif
            </p>
            <a href="{{ route('admin.tenants.index', ['plan' => $slug]) }}" class="text-sm text-slate-700 hover:text-slate-900">View schools →</a>
        </div>
    @endforeach
</div>

<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <h2 class="text-sm font-semibold text-slate-700 uppercase tracking-wide">Activation codes</h2>
        <div class="flex items-center gap-2 text-sm">
            <span class="text-slate-500">Filter:</span>
            @foreach (['all' => 'Active (no redeemed)', 'unredeemed' => 'Unredeemed', 'redeemed' => 'Redeemed', 'revoked' => 'Revoked', 'expired' => 'Expired'] as $key => $label)
                <a href="{{ route('admin.plans.index', ['status' => $key]) }}"
                   class="px-2 py-1 rounded {{ $statusFilter === $key ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' }}">{{ $label }}</a>
            @endforeach
        </div>
    </div>
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Tenant</th>
                <th class="px-6 py-3">Code</th>
                <th class="px-6 py-3">Plan</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Generated</th>
                <th class="px-6 py-3">Expires</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-200 text-sm text-slate-700">
            @forelse ($codes as $code)
                <tr>
                    <td class="px-6 py-3"><a href="{{ route('admin.tenants.show', $code->tenant) }}" class="text-slate-900 hover:underline">{{ $code->tenant->name }}</a></td>
                    <td class="px-6 py-3"><code class="font-mono text-xs">{{ $code->code }}</code></td>
                    <td class="px-6 py-3">{{ config('plans.' . $code->plan . '.name', $code->plan) }}</td>
                    <td class="px-6 py-3">
                        @php
                            $color = match($code->status) {
                                'unredeemed' => 'bg-yellow-100 text-yellow-800',
                                'redeemed'   => 'bg-green-100 text-green-800',
                                'revoked'    => 'bg-slate-200 text-slate-700',
                                'expired'    => 'bg-red-100 text-red-800',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">{{ $code->status }}</span>
                    </td>
                    <td class="px-6 py-3 text-slate-500">{{ $code->created_at->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-slate-500">
                        @if ($code->status === 'unredeemed')
                            in {{ now()->diffInDays($code->expires_at) }} days
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-6 py-3 text-right">
                        @if ($code->status === 'unredeemed')
                            <form method="POST" action="{{ route('admin.tenants.codes.revoke', [$code->tenant, $code]) }}" class="inline" onsubmit="return confirm('Revoke this code? It can no longer be redeemed.');">
                                @csrf
                                <button class="text-xs text-red-700 hover:text-red-900">Revoke</button>
                            </form>
                        @elseif (in_array($code->status, ['revoked', 'expired'], true))
                            <form method="POST" action="{{ route('admin.tenants.codes.regenerate', $code->tenant) }}" class="inline">
                                @csrf
                                <button class="text-xs text-slate-700 hover:text-slate-900">Regenerate</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-6 py-12 text-center text-slate-500">No codes match this filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
