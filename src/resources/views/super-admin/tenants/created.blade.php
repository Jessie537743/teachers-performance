@extends('super-admin.layout', ['title' => 'School created'])

@section('content')
<div class="max-w-2xl bg-white shadow rounded-lg p-8">
    <div class="flex items-center gap-3 mb-6">
        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-lg">✓</div>
        <h1 class="text-xl font-semibold text-slate-900">{{ $tenant->name }} is provisioned.</h1>
    </div>

    <p class="text-sm text-slate-600 mb-6">
        The school's database is ready. Send the activation code below to <code class="font-mono">{{ $activationCode->intended_admin_email }}</code> — they'll redeem it to set their own password and finish onboarding.
    </p>

    <dl class="space-y-4 text-sm mb-6">
        <div>
            <dt class="text-slate-500 mb-1">Activation code (shown once)</dt>
            <dd class="font-mono text-2xl tracking-wider select-all bg-yellow-50 border border-yellow-200 rounded px-4 py-3 text-center">{{ $activationCode->code }}</dd>
        </div>
        <div>
            <dt class="text-slate-500 mb-1">Activation URL</dt>
            <dd class="text-slate-900 font-mono text-xs select-all">{{ url('/activate?code=' . $activationCode->code) }}</dd>
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <dt class="text-slate-500 mb-1">Plan</dt>
                <dd class="text-slate-900 font-medium">{{ config('plans.' . $activationCode->plan . '.name') }}</dd>
            </div>
            <div>
                <dt class="text-slate-500 mb-1">Expires</dt>
                <dd class="text-slate-900">{{ $activationCode->expires_at->toDayDateTimeString() }}</dd>
            </div>
        </div>
        <div>
            <dt class="text-slate-500 mb-1">Intended admin</dt>
            <dd class="text-slate-900">{{ $activationCode->intended_admin_name }} &lt;{{ $activationCode->intended_admin_email }}&gt;</dd>
        </div>
    </dl>

    <p class="text-sm text-slate-500 mb-6">If they lose this code, you can revoke + regenerate from the school's detail page.</p>

    <div class="flex items-center justify-between">
        <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">← Back to schools</a>
        <a href="{{ route('admin.tenants.show', $tenant) }}" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Open school detail
        </a>
    </div>
</div>
@endsection
