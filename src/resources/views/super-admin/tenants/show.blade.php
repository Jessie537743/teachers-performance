@extends('super-admin.layout', ['title' => $tenant->name])

@section('content')
<div class="mb-4">
    <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← All schools</a>
</div>

<div class="bg-white shadow rounded-lg p-6 mb-6">
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">{{ $tenant->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                <a href="http://{{ $tenant->subdomain }}.localhost:8081" target="_blank" class="text-slate-700 hover:text-slate-900 underline">
                    {{ $tenant->subdomain }}.localhost:8081
                </a>
            </p>
        </div>
        @php
            $color = match($tenant->status) {
                'active' => 'bg-green-100 text-green-800',
                'provisioning' => 'bg-yellow-100 text-yellow-800',
                'suspended' => 'bg-slate-200 text-slate-700',
                'failed' => 'bg-red-100 text-red-800',
            };
        @endphp
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $color }}">{{ $tenant->status }}</span>
    </div>

    <dl class="mt-6 grid grid-cols-2 gap-4 text-sm">
        <div>
            <dt class="text-slate-500">Database</dt>
            <dd class="font-mono text-slate-900">{{ $tenant->getAttribute('database') }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Created</dt>
            <dd class="text-slate-900">{{ $tenant->created_at?->toDayDateTimeString() }}</dd>
        </div>
    </dl>
</div>

@if ($jobs->isNotEmpty())
<div class="bg-white shadow rounded-lg p-6 mb-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Provisioning history</h2>
    <ul class="divide-y divide-slate-200">
        @foreach ($jobs as $job)
            <li class="py-2 text-sm flex items-start justify-between gap-4">
                <div>
                    <span class="font-medium text-slate-900">{{ ucfirst($job->status) }}</span>
                    <span class="text-slate-500"> — {{ $job->created_at->toDayDateTimeString() }}</span>
                    @if ($job->error)
                        <pre class="mt-1 bg-red-50 border border-red-200 rounded p-2 text-xs text-red-700 whitespace-pre-wrap">{{ $job->error }}</pre>
                    @endif
                </div>
            </li>
        @endforeach
    </ul>
</div>
@endif

<div class="bg-white shadow rounded-lg p-6">
    <h2 class="text-sm font-semibold text-slate-700 mb-3 uppercase tracking-wide">Actions</h2>
    <div class="flex flex-wrap items-center gap-3">
        @if ($tenant->status === 'active')
            <form method="POST" action="{{ route('admin.tenants.suspend', $tenant) }}" onsubmit="return confirm('Suspend {{ $tenant->name }}? Logins will be blocked.');">
                @csrf
                <button class="rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">Suspend school</button>
            </form>
        @endif

        @if ($tenant->status === 'suspended')
            <form method="POST" action="{{ route('admin.tenants.resume', $tenant) }}">
                @csrf
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Resume school</button>
            </form>
        @endif

        @if ($tenant->status === 'failed')
            <form method="POST" action="{{ route('admin.tenants.retry', $tenant) }}">
                @csrf
                <button class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white hover:bg-slate-800">Retry provisioning</button>
            </form>
        @endif
    </div>
</div>
@endsection
