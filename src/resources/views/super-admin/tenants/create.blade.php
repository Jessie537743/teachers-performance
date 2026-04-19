@extends('super-admin.layout', ['title' => 'New school'])

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-semibold text-slate-900 mb-1">Provision a new school</h1>
    <p class="text-sm text-slate-500 mb-6">This creates a fresh tenant database, runs migrations, seeds the blank-school template, and generates an activation code for the first admin.</p>

    <form method="POST" action="{{ route('admin.tenants.store') }}" class="bg-white shadow rounded-lg p-6 space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">School name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="120"
                placeholder="e.g. St. Mary's Academy"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="subdomain" class="block text-sm font-medium text-slate-700 mb-1">Subdomain</label>
            <div class="flex items-center">
                <input id="subdomain" name="subdomain" type="text" value="{{ old('subdomain') }}" required
                    pattern="[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?"
                    placeholder="stmarys"
                    class="flex-1 rounded-l-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <span class="inline-flex items-center rounded-r-md border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm text-slate-500">.{{ str_replace('admin.', '', env('APP_ADMIN_DOMAIN', 'admin.localhost')) }}:8081</span>
            </div>
            <p class="mt-1 text-xs text-slate-500">2-32 chars, lowercase letters / digits / hyphens. Reserved: admin, www, api, app, mail, ftp, cdn.</p>
            @error('subdomain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <hr class="border-slate-200">

        <div>
            <label for="admin_name" class="block text-sm font-medium text-slate-700 mb-1">First admin — name</label>
            <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required maxlength="120"
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            @error('admin_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-1">First admin — email</label>
            <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            <p class="mt-1 text-xs text-slate-500">An activation code will be generated — they'll redeem it to set their own password.</p>
            @error('admin_email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <hr class="border-slate-200">

        <div>
            <label for="plan" class="block text-sm font-medium text-slate-700 mb-1">Plan</label>
            <select id="plan" name="plan" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                @foreach (config('plans') as $slug => $plan)
                    <option value="{{ $slug }}" {{ old('plan', 'free') === $slug ? 'selected' : '' }}>
                        {{ $plan['name'] }} —
                        @if (is_numeric($plan['price']))
                            ${{ $plan['price'] }} {{ $plan['period'] }}
                        @else
                            {{ $plan['price'] }}
                        @endif
                    </option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">{{ config('plans.' . old('plan', 'free') . '.tagline') }}</p>
            @error('plan') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('admin.tenants.index') }}" class="text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Provision school
            </button>
        </div>
    </form>
</div>
@endsection
