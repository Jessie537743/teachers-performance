@extends('super-admin.layout', ['title' => 'Sign in'])

@section('content')
<div class="min-h-screen grid lg:grid-cols-2">

    {{-- Left: brand panel --}}
    <div class="relative hidden lg:flex flex-col justify-between overflow-hidden text-white"
         style="background: radial-gradient(1200px 600px at 10% 0%, rgba(96,165,250,0.45), transparent 60%), radial-gradient(800px 500px at 90% 100%, rgba(59,130,246,0.4), transparent 60%), linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #2563eb 100%);">
        <div class="absolute -top-20 -left-20 w-80 h-80 rounded-full bg-white/5"></div>
        <div class="absolute top-1/3 -right-16 w-72 h-72 rounded-full bg-white/5"></div>
        <div class="absolute bottom-10 left-1/3 w-40 h-40 rounded-full bg-white/5"></div>

        <div class="relative p-10 flex items-center gap-3">
            <img src="{{ asset(config('app.default_logo')) }}" alt="" class="w-10 h-10">
            <div>
                <div class="font-bold tracking-tight">{{ config('app.name', 'Teachers Performance') }}</div>
                <div class="text-xs text-blue-200/80">Platform Console</div>
            </div>
        </div>

        <div class="relative px-10 pb-16 max-w-md">
            <h2 class="text-3xl xl:text-4xl font-bold leading-tight mb-4">
                One control plane for every school.
            </h2>
            <p class="text-blue-100/90 text-base mb-8">
                Provision tenants, manage plans, mint activation codes, and monitor onboarding — all from one console.
            </p>

            <div class="space-y-3 text-sm">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-white/10 ring-1 ring-white/20 grid place-items-center">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4L9 11.6l6.3-6.3a1 1 0 011.4 0z"/></svg>
                    </span>
                    <span class="text-blue-100/90">Sub-30s tenant provisioning</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-white/10 ring-1 ring-white/20 grid place-items-center">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4L9 11.6l6.3-6.3a1 1 0 011.4 0z"/></svg>
                    </span>
                    <span class="text-blue-100/90">Per-school isolated databases</span>
                </div>
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-white/10 ring-1 ring-white/20 grid place-items-center">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4L9 11.6l6.3-6.3a1 1 0 011.4 0z"/></svg>
                    </span>
                    <span class="text-blue-100/90">Plan-based feature gating</span>
                </div>
            </div>
        </div>

        <div class="relative px-10 pb-8 text-xs text-blue-200/70 flex items-center gap-3">
            <span class="inline-flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Secure session</span>
            <span>•</span>
            <span>Platform staff only</span>
        </div>
    </div>

    {{-- Right: form --}}
    <div class="flex items-center justify-center px-6 py-12 bg-slate-50">
        <div class="w-full max-w-sm">
            {{-- Mobile brand --}}
            <div class="lg:hidden flex items-center justify-center gap-2 mb-8">
                <img src="{{ asset(config('app.default_logo')) }}" alt="" class="w-10 h-10">
                <div class="font-bold tracking-tight text-slate-900">{{ config('app.name', 'Teachers Performance') }}</div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-200/70 p-8">
                <div class="mb-6 text-center">
                    <h1 class="text-2xl font-bold text-slate-900">Welcome back</h1>
                    <p class="text-sm text-slate-500 mt-1">Sign in to the Platform Console.</p>
                </div>

                @if ($errors->any())
                    <div class="mb-5 rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-xs text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </span>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                                placeholder="you@platform.test"
                                class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition">
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="password" class="block text-sm font-medium text-slate-700">Password</label>
                        </div>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-3 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 11c0-1.66-1.34-3-3-3s-3 1.34-3 3v3a3 3 0 003 3h0a3 3 0 003-3v-3zm6 0V8a6 6 0 00-12 0v3M5 11h14a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2z"/></svg>
                            </span>
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                placeholder="••••••••"
                                class="w-full pl-10 pr-3 py-2.5 rounded-lg border border-slate-300 bg-white text-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/30 focus:outline-none transition">
                        </div>
                    </div>

                    <label class="flex items-center gap-2 select-none cursor-pointer text-sm text-slate-600">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Remember me on this device
                    </label>

                    <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-brand-700 to-brand-900 hover:from-brand-800 hover:to-brand-950 text-white font-semibold py-2.5 shadow-lg shadow-brand-900/20 transition">
                        Sign in
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </form>

                <p class="text-xs text-slate-500 text-center mt-6">
                    Restricted to platform staff. Tenants sign in via their school subdomain.
                </p>
            </div>

            <p class="text-xs text-slate-400 text-center mt-6">
                &copy; {{ date('Y') }} {{ config('app.name', 'Teachers Performance Platform') }}
            </p>
        </div>
    </div>
</div>
@endsection
