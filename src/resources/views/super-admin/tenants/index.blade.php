@extends('super-admin.layout', ['title' => 'Schools'])

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-slate-900">Schools</h1>
        <p class="text-sm text-slate-500">{{ $tenants->count() }} {{ Str::plural('school', $tenants->count()) }} registered.</p>
    </div>
    <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
        New school
    </a>
</div>

@if ($pendingCount > 0)
    <div class="mb-4 flex items-center gap-3">
        <span class="text-xs text-slate-500 uppercase tracking-wide">Filter:</span>
        @if ($statusFilter === 'pending_activation')
            <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs text-white">{{ $pendingCount }} pending activation ✕</a>
        @else
            <a href="{{ route('admin.tenants.index', ['status' => 'pending_activation']) }}" class="inline-flex items-center rounded-full bg-yellow-100 px-3 py-1 text-xs text-yellow-800 hover:bg-yellow-200">{{ $pendingCount }} pending activation</a>
        @endif

        @if ($planFilter)
            <a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs text-white">plan: {{ $planFilter }} ✕</a>
        @endif
    </div>
@endif

<div class="bg-white shadow rounded-lg overflow-hidden">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-xs font-medium uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-6 py-3">Name</th>
                <th class="px-6 py-3">Subdomain</th>
                <th class="px-6 py-3">Plan</th>
                <th class="px-6 py-3">Status</th>
                <th class="px-6 py-3">Created</th>
                <th class="px-6 py-3"></th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-slate-200 text-sm text-slate-700">
            @forelse ($tenants as $tenant)
                <tr>
                    <td class="px-6 py-3 font-medium text-slate-900">{{ $tenant->name }}</td>
                    <td class="px-6 py-3"><code class="text-xs">{{ $tenant->subdomain }}</code></td>
                    <td class="px-6 py-3">
                        @php
                            $planColor = match($tenant->plan) {
                                'free'       => 'bg-slate-100 text-slate-700',
                                'pro'        => 'bg-blue-100 text-blue-800',
                                'enterprise' => 'bg-purple-100 text-purple-800',
                                default      => 'bg-slate-100 text-slate-700',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $planColor }}">{{ config('plans.' . $tenant->plan . '.name', $tenant->plan) }}</span>
                    </td>
                    <td class="px-6 py-3">
                        @php
                            $color = match($tenant->status) {
                                'active'             => 'bg-green-100 text-green-800',
                                'provisioning'       => 'bg-yellow-100 text-yellow-800',
                                'pending_activation' => 'bg-yellow-100 text-yellow-800',
                                'suspended'          => 'bg-slate-200 text-slate-700',
                                'failed'             => 'bg-red-100 text-red-800',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $color }}">{{ $tenant->status }}</span>
                    </td>
                    <td class="px-6 py-3 text-slate-500">{{ $tenant->created_at?->diffForHumans() }}</td>
                    <td class="px-6 py-3 text-right">
                        <a href="{{ route('admin.tenants.show', $tenant) }}" class="text-slate-700 hover:text-slate-900">View →</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No schools match the current filter.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
