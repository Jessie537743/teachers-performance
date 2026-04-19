@extends('super-admin.layout', ['title' => 'School created'])

@section('content')
<div class="max-w-xl bg-white shadow rounded-lg p-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-lg">✓</div>
        <h1 class="text-xl font-semibold text-slate-900">{{ $tenant->name }} is ready.</h1>
    </div>

    <dl class="space-y-3 text-sm mb-6">
        <div>
            <dt class="text-slate-500">School URL</dt>
            <dd class="text-slate-900 font-mono">
                <a href="http://{{ $tenant->subdomain }}.localhost:8081" class="underline hover:text-slate-700" target="_blank">
                    http://{{ $tenant->subdomain }}.localhost:8081
                </a>
            </dd>
        </div>
        <div>
            <dt class="text-slate-500">Admin email</dt>
            <dd class="text-slate-900 font-mono">{{ $adminEmail }}</dd>
        </div>
        <div>
            <dt class="text-slate-500">Temporary password (shown once)</dt>
            <dd class="text-slate-900 font-mono select-all bg-yellow-50 border border-yellow-200 rounded px-3 py-2">{{ $tempPassword }}</dd>
        </div>
    </dl>

    <p class="text-sm text-slate-500 mb-6">Send these credentials to the school admin. They will be required to change the password on first login.</p>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to schools</a>
        <a href="http://{{ $tenant->subdomain }}.localhost:8081" target="_blank" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Open school dashboard
        </a>
    </div>
</div>
@endsection
