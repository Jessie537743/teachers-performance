@extends('super-admin.layout', [
    'title' => 'Provision a new school',
    'subtitle' => 'Creates a tenant DB, runs migrations, and mints an activation code',
])

@section('content')
<a href="{{ route('admin.tenants.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-700 mb-4">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
    All schools
</a>

<form method="POST" action="{{ route('admin.tenants.store') }}" class="grid lg:grid-cols-3 gap-6">
    @csrf

    {{-- Form column --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">School details</h2>
            <div class="space-y-5">
                <div>
                    <label for="name" class="block text-sm font-medium text-slate-700 mb-1.5">School name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="120"
                        placeholder="e.g. St. Mary's Academy"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                    @error('name') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="subdomain" class="block text-sm font-medium text-slate-700 mb-1.5">Subdomain</label>
                    <div class="flex items-center">
                        <input id="subdomain" name="subdomain" type="text" value="{{ old('subdomain') }}" required
                            pattern="[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?"
                            placeholder="stmarys"
                            class="flex-1 rounded-l-lg border-slate-300 text-sm font-mono lowercase focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                        @php
                            $appUrlParts = parse_url((string) config('app.url', 'http://localhost'));
                            $portSuffix  = isset($appUrlParts['port']) ? ':' . $appUrlParts['port'] : '';
                        @endphp
                        <span class="inline-flex items-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-500 font-mono">
                            .{{ preg_replace('/^admin\./', '', env('APP_ADMIN_DOMAIN', 'admin.localhost')) }}{{ $portSuffix }}
                        </span>
                    </div>
                    <p class="mt-1.5 text-xs text-slate-500">2-32 chars, lowercase letters / digits / hyphens. Reserved: admin, www, api, app, mail, ftp, cdn.</p>
                    @error('subdomain') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-1">First admin</h2>
            <p class="text-xs text-slate-500 mb-4">An activation code will be generated for this admin to set their own password.</p>
            <div class="space-y-5">
                <div>
                    <label for="admin_name" class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
                    <input id="admin_name" name="admin_name" type="text" value="{{ old('admin_name') }}" required maxlength="120"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                    @error('admin_name') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30">
                    @error('admin_email') <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-6">
            <h2 class="text-sm font-semibold text-slate-900 mb-4">Plan</h2>
            <div class="grid sm:grid-cols-3 gap-3">
                @foreach (config('plans') as $slug => $plan)
                    <label class="relative cursor-pointer">
                        <input type="radio" name="plan" value="{{ $slug }}" class="peer sr-only"
                               {{ old('plan', 'free') === $slug ? 'checked' : '' }}>
                        <div class="rounded-xl border-2 border-slate-200 p-4 hover:border-slate-300 peer-checked:border-brand-600 peer-checked:bg-brand-50/50 peer-checked:shadow-sm transition">
                            <div class="flex items-center justify-between">
                                <span class="font-semibold text-slate-900">{{ $plan['name'] }}</span>
                                @if ($plan['highlight'])
                                    <span class="text-[10px] uppercase tracking-wider px-1.5 py-0.5 rounded bg-brand-600 text-white font-semibold">Popular</span>
                                @endif
                            </div>
                            <div class="mt-1 text-sm text-slate-700">
                                @if (is_numeric($plan['price']))
                                    <span class="font-bold">${{ $plan['price'] }}</span>
                                    <span class="text-xs text-slate-500">{{ $plan['period'] }}</span>
                                @else
                                    <span class="font-bold">{{ $plan['price'] }}</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-500 mt-1.5 leading-snug">{{ $plan['tagline'] }}</p>
                        </div>
                        <span class="absolute top-3 right-3 hidden peer-checked:flex w-5 h-5 rounded-full bg-brand-600 text-white items-center justify-center">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('plan') <p class="mt-2 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Side summary --}}
    <aside class="space-y-4 lg:sticky lg:top-24 self-start">
        <div class="bg-white rounded-xl ring-1 ring-slate-200 p-5">
            <h3 class="text-sm font-semibold text-slate-900 mb-2">What happens next</h3>
            <ol class="space-y-2 text-xs text-slate-600">
                <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-brand-100 text-brand-700 grid place-items-center text-[10px] font-bold flex-shrink-0">1</span> Create tenant DB &amp; domain</li>
                <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-brand-100 text-brand-700 grid place-items-center text-[10px] font-bold flex-shrink-0">2</span> Run migrations &amp; seed template</li>
                <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-brand-100 text-brand-700 grid place-items-center text-[10px] font-bold flex-shrink-0">3</span> Mint a 30-day activation code</li>
                <li class="flex gap-2"><span class="w-4 h-4 rounded-full bg-brand-100 text-brand-700 grid place-items-center text-[10px] font-bold flex-shrink-0">4</span> Show you the code to share</li>
            </ol>
        </div>

        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-brand-700 to-brand-900 hover:from-brand-800 hover:to-brand-950 text-white text-sm font-semibold px-4 py-3 shadow-lg shadow-brand-900/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            Provision school
        </button>
        <a href="{{ route('admin.tenants.index') }}" class="block text-center text-sm text-slate-500 hover:text-slate-700">Cancel</a>
    </aside>
</form>
@endsection
