<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Platform Console' }} — {{ config('app.name', 'Teachers Performance') }}</title>

    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('app.default_logo')) }}">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: {
                            50:  '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd',
                            400: '#60a5fa', 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8',
                            800: '#1e40af', 900: '#1e3a8a',
                        },
                    },
                },
            },
        };
    </script>
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        [x-cloak] { display: none !important; }
        .nav-link.active { background: rgba(255,255,255,0.15); color: #fff; border-left: 3px solid #93c5fd; padding-left: calc(0.875rem - 3px); }
        .scrollbar-thin::-webkit-scrollbar { width: 6px; height: 6px; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.4); border-radius: 9999px; }
    </style>
    @stack('head')
</head>
<body class="h-full bg-slate-50 text-slate-800 antialiased">

@auth('super_admin')
<div class="min-h-screen flex">
    {{-- Sidebar --}}
    <aside class="hidden lg:flex w-64 flex-col bg-gradient-to-b from-brand-900 via-brand-800 to-brand-900 text-white">
        <div class="h-16 px-5 flex items-center gap-3 border-b border-white/10">
            <img src="{{ asset(config('app.default_logo')) }}" class="w-8 h-8" alt="">
            <div class="leading-tight">
                <div class="font-bold text-sm tracking-tight">Platform Console</div>
                <div class="text-[11px] text-blue-200/80">Multi-tenant control plane</div>
            </div>
        </div>

        <nav class="flex-1 p-3 space-y-1 overflow-y-auto scrollbar-thin">
            <p class="px-3 pt-3 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/40">Manage</p>
            <a href="{{ route('admin.tenants.index') }}"
               class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm text-white/80 hover:bg-white/10 hover:text-white transition {{ request()->routeIs('admin.tenants.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Schools
            </a>
            <a href="{{ route('admin.plans.index') }}"
               class="nav-link flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm text-white/80 hover:bg-white/10 hover:text-white transition {{ request()->routeIs('admin.plans.*') ? 'active' : '' }}">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                Plans &amp; codes
            </a>

            <p class="px-3 pt-6 pb-1 text-[11px] font-semibold uppercase tracking-widest text-white/40">Quick</p>
            <a href="{{ route('admin.tenants.create') }}" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm text-white/80 hover:bg-white/10 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Provision school
            </a>
            <a href="{{ url('/') }}" target="_blank" class="flex items-center gap-3 px-3.5 py-2.5 rounded-lg text-sm text-white/60 hover:bg-white/10 hover:text-white transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                Open marketplace
            </a>
        </nav>

        <div class="p-3 border-t border-white/10">
            <div class="rounded-xl bg-white/5 px-3 py-2.5 flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand-500 grid place-items-center text-xs font-bold uppercase">
                    {{ substr(auth('super_admin')->user()->email, 0, 1) }}
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[12px] font-semibold truncate">{{ auth('super_admin')->user()->name ?? 'Super Admin' }}</div>
                    <div class="text-[11px] text-blue-200/70 truncate">{{ auth('super_admin')->user()->email }}</div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-blue-200/70 hover:text-white" title="Sign out">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Main column --}}
    <div class="flex-1 flex flex-col min-w-0">
        {{-- Mobile top bar (sidebar substitute) --}}
        <header class="lg:hidden h-14 bg-brand-900 text-white flex items-center justify-between px-4 shadow-sm">
            <div class="flex items-center gap-2">
                <img src="{{ asset(config('app.default_logo')) }}" class="w-7 h-7" alt="">
                <span class="font-semibold text-sm">Platform Console</span>
            </div>
            <details class="relative">
                <summary class="list-none cursor-pointer p-1.5 rounded-md hover:bg-white/10">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </summary>
                <div class="absolute right-0 top-10 z-30 w-56 bg-white text-slate-700 rounded-lg shadow-xl border border-slate-200 py-2 text-sm">
                    <a href="{{ route('admin.tenants.index') }}" class="block px-4 py-2 hover:bg-slate-50">Schools</a>
                    <a href="{{ route('admin.plans.index') }}" class="block px-4 py-2 hover:bg-slate-50">Plans &amp; codes</a>
                    <a href="{{ route('admin.tenants.create') }}" class="block px-4 py-2 hover:bg-slate-50">Provision school</a>
                    <hr class="my-2 border-slate-200">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-slate-50">Sign out</button>
                    </form>
                </div>
            </details>
        </header>

        {{-- Topbar (desktop) --}}
        <header class="hidden lg:flex h-16 bg-white/80 backdrop-blur sticky top-0 z-20 border-b border-slate-200 px-6 items-center justify-between">
            <div>
                <h1 class="text-base font-semibold text-slate-900 leading-tight">{{ $title ?? 'Dashboard' }}</h1>
                <p class="text-xs text-slate-500">{{ $subtitle ?? 'Manage tenants, plans, and provisioning' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <span class="hidden xl:inline-flex items-center gap-2 text-xs text-slate-500">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                    All systems operational
                </span>
                <a href="{{ route('admin.tenants.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 hover:bg-brand-700 text-white text-sm font-medium px-4 py-2 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    New school
                </a>
            </div>
        </header>

        <main class="flex-1 px-4 sm:px-6 lg:px-8 py-6 lg:py-8">
            @if (session('status'))
                <div class="mb-6 flex items-start gap-3 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">
                    <svg class="w-5 h-5 flex-shrink-0 text-emerald-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.454 5.454A8 8 0 116.546 6.546a8 8 0 0112.908 12.908z"/></svg>
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 flex items-start gap-3 rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">
                    <svg class="w-5 h-5 flex-shrink-0 text-rose-600 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01M5.07 19h13.86c1.54 0 2.5-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>
@else
    {{-- Unauthenticated layout (login) --}}
    <main class="min-h-screen">
        {{ $slot ?? '' }}
        @yield('content')
    </main>
@endauth

</body>
</html>
