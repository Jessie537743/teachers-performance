<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Segoe UI"', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'] },
                    colors: { primary: { DEFAULT: '#2563eb', dark: '#1d4ed8', light: '#60a5fa', 50: '#eff6ff' } },
                    animation: {
                        'fade-in': 'fadeIn .3s ease-out',
                        'slide-up': 'slideUp .35s ease-out',
                        'slide-up-delayed': 'slideUp .4s ease-out both',
                        'alert-in': 'alertIn .35s ease-out',
                        'spin-slow': 'spin .7s linear infinite',
                        'pop': 'pop .2s ease',
                    },
                    keyframes: {
                        fadeIn: { from: { opacity: '0', transform: 'translateY(8px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                        slideUp: { from: { opacity: '0', transform: 'translateY(16px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                        alertIn: { from: { opacity: '0', transform: 'translateY(-10px)' }, to: { opacity: '1', transform: 'translateY(0)' } },
                        pop: { '0%': { transform: 'scale(1)' }, '50%': { transform: 'scale(1.2)' }, '100%': { transform: 'scale(1)' } },
                    }
                }
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8/dist/turbo.es2017.esm.js" type="module"></script>
    <style>
        .turbo-progress-bar { height: 3px; background: #2563eb; }
        [x-cloak] { display: none !important; }
        /* Sidebar width */
        .sidebar-w { width: 260px; min-width: 260px; }
        .main-ml { margin-left: 260px; }
        @media (max-width: 1024px) {
            .main-ml { margin-left: 0; }
            .sidebar-w { transform: translateX(-100%); }
            body.sidebar-open .sidebar-w { transform: translateX(0); }
            body.sidebar-open .sidebar-overlay-bg { opacity: 1; visibility: visible; }
            .menu-toggle-btn { display: flex !important; }
        }
        /* Stagger stat cards */
        .stat-stagger:nth-child(1) { animation-delay: .05s; }
        .stat-stagger:nth-child(2) { animation-delay: .1s; }
        .stat-stagger:nth-child(3) { animation-delay: .15s; }
        .stat-stagger:nth-child(4) { animation-delay: .2s; }
        .stat-stagger:nth-child(5) { animation-delay: .25s; }
        .stat-stagger:nth-child(6) { animation-delay: .3s; }
        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-50 font-sans text-slate-900 antialiased">

{{-- Form loading overlay --}}
<div class="fixed inset-0 z-[9999] hidden items-center justify-center bg-slate-900/35 backdrop-blur-sm" id="formLoadingOverlay">
    <div class="flex flex-col items-center gap-4 rounded-2xl bg-white px-10 py-8 shadow-2xl">
        <div class="h-10 w-10 animate-spin-slow rounded-full border-4 border-gray-200 border-t-primary"></div>
        <div class="text-sm font-semibold text-gray-700" id="formLoadingText">Processing...</div>
    </div>
</div>

{{-- Sidebar overlay --}}
<div class="sidebar-overlay-bg fixed inset-0 z-[999] bg-slate-900/45 opacity-0 invisible transition-all duration-300" id="sidebarOverlay"></div>

<div class="flex min-h-screen">
    {{-- Sidebar --}}
    <aside class="sidebar-w fixed inset-y-0 left-0 z-[1000] flex flex-col bg-gradient-to-b from-slate-900 to-slate-800 text-white shadow-lg transition-transform duration-300" id="sidebar">
        <div class="flex h-[72px] items-center gap-3 border-b border-white/10 px-5">
            <div class="grid h-10 w-10 flex-shrink-0 place-items-center overflow-hidden rounded-xl bg-white/10">
                @php $customLogo = \App\Models\Setting::get('app_logo'); @endphp
                <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset('images/smcc_logo.png') }}" alt="Logo" class="h-full w-full object-cover">
            </div>
            <span class="text-lg font-bold truncate">{{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</span>
        </div>
        <div class="flex-1 overflow-y-auto px-3 py-4">
            @include('layouts.partials.sidebar')
        </div>
        <div class="border-t border-white/10 p-3.5">
            <div class="flex items-center gap-2.5 px-2 py-1 text-xs text-white/60">
                <div class="grid h-7 w-7 flex-shrink-0 place-items-center rounded-full bg-primary text-[11px] font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div class="overflow-hidden">
                    <div class="truncate text-[13px] font-semibold text-white/90">{{ auth()->user()->name }}</div>
                    <div class="truncate text-[11px] capitalize">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                </div>
            </div>
        </div>
    </aside>

    {{-- Main --}}
    <main class="main-ml flex min-w-0 flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-[900] flex h-[72px] items-center justify-between border-b border-gray-200 bg-white/80 px-5 backdrop-blur-xl">
            <div class="flex items-center gap-3.5">
                <button class="menu-toggle-btn hidden h-11 w-11 items-center justify-center rounded-xl border border-gray-200 bg-white text-lg text-slate-900 hover:bg-gray-50" id="menuToggle">&#9776;</button>
                <h1 class="text-xl font-bold">@yield('page-title', 'Dashboard')</h1>
            </div>
            <div class="relative" id="userChip">
                <button onclick="document.getElementById('userDropdown').classList.toggle('show')" class="flex items-center gap-2.5 rounded-full border border-gray-200 bg-white px-3 py-2 transition-all hover:border-primary-light hover:shadow-sm">
                    <div class="grid h-9 w-9 place-items-center rounded-full bg-primary font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                    <span class="hidden sm:inline text-sm font-medium">{{ auth()->user()->name }}</span>
                    <svg width="12" height="12" viewBox="0 0 12 12" fill="none" class="ml-1 opacity-60"><path d="M3 4.5L6 7.5L9 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>

                {{-- Dropdown --}}
                <div id="userDropdown" class="user-dropdown absolute right-0 top-[calc(100%+8px)] z-[3000] min-w-[260px] overflow-hidden rounded-xl border border-gray-200 bg-white opacity-0 invisible -translate-y-2 scale-[.97] transition-all duration-200 shadow-xl">
                    <div class="flex items-center gap-3 p-4">
                        <div class="grid h-9 w-9 place-items-center rounded-full bg-primary text-[15px] font-bold text-white">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                        <div>
                            <div class="text-sm font-semibold text-slate-800">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-slate-500">{{ auth()->user()->email }}</div>
                            <div class="text-[11px] capitalize text-slate-400">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                        </div>
                    </div>
                    <div class="h-px bg-gray-200"></div>
                    <a href="{{ route('account.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        My Account
                    </a>
                    <a href="{{ route('password.change') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        Change Password
                    </a>
                    @can('manage-settings')
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-gray-700 transition hover:bg-gray-50">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-slate-400"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        Settings
                    </a>
                    @endcan
                    <div class="h-px bg-gray-200"></div>
                    <form method="POST" action="{{ route('logout') }}" class="m-0" data-turbo="false">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2.5 px-4 py-2.5 text-sm text-red-500 transition hover:bg-red-50">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="text-red-500"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Content --}}
        <section class="animate-fade-in w-full p-6 lg:p-6 md:p-5 sm:p-4">
            @if(session('success'))
                <div class="mb-4 animate-alert-in rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if(session('status'))
                <div class="mb-4 animate-alert-in rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-green-800">{{ session('status') }}</div>
            @endif
            @if(session('info'))
                <div class="mb-4 animate-alert-in rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-amber-800">{{ session('info') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 animate-alert-in rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 animate-alert-in rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                    <ul class="m-0 list-disc pl-5">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </section>
    </main>
</div>

<script src="{{ asset('js/custom.js') }}" data-turbo-permanent></script>
<script data-turbo-permanent>
    if (!window._appListenersAttached) {
        window._appListenersAttached = true;

        document.addEventListener('click', function(e) {
            var toggle = e.target.closest('#menuToggle');
            if (toggle) { document.body.classList.toggle('sidebar-open'); return; }
            if (e.target.id === 'sidebarOverlay') { document.body.classList.remove('sidebar-open'); return; }

            var dropdown = document.getElementById('userDropdown');
            var chip = document.getElementById('userChip');
            if (dropdown && chip && !chip.contains(e.target)) dropdown.classList.remove('show');

            var link = e.target.closest('.ajax-pagination a');
            if (!link) return;
            e.preventDefault();
            var card = link.closest('[data-card]');
            if (!card) { window.location.href = link.href; return; }
            card.classList.add('opacity-50', 'pointer-events-none');
            fetch(link.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function(r) { return r.text(); })
                .then(function(html) {
                    var doc = new DOMParser().parseFromString(html, 'text/html');
                    var title = card.querySelector('[data-card-title]');
                    var newCard = null;
                    if (title) {
                        var txt = title.textContent.trim();
                        doc.querySelectorAll('[data-card-title]').forEach(function(t) {
                            if (t.textContent.trim() === txt) newCard = t.closest('[data-card]');
                        });
                    }
                    if (!newCard) newCard = doc.querySelector('[data-card]');
                    if (newCard) card.innerHTML = newCard.innerHTML;
                    card.classList.remove('opacity-50', 'pointer-events-none');
                    history.pushState(null, '', link.href);
                    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(function() { card.classList.remove('opacity-50', 'pointer-events-none'); window.location.href = link.href; });
        });

        window.addEventListener('popstate', function() { location.reload(); });

        document.addEventListener('submit', function(e) {
            var form = e.target;
            if (!form || form.tagName !== 'FORM') return;
            if (form.method.toUpperCase() === 'GET') return;
            if (form.hasAttribute('data-no-loading')) return;
            var btn = form.querySelector('button[type="submit"]') || form.querySelector('[type="submit"]');
            var message = 'Processing...';
            if (btn) {
                var t = btn.textContent.trim();
                if (/submit.*evaluation/i.test(t)) message = 'Submitting evaluation...';
                else if (/save/i.test(t)) message = 'Saving...';
                else if (/update/i.test(t)) message = 'Updating...';
                else if (/delete|remove|destroy/i.test(t)) message = 'Deleting...';
                else if (/reset/i.test(t)) message = 'Resetting...';
                else if (/logout|log\s*out|sign\s*out/i.test(t)) message = 'Signing out...';
                else if (/sign|log/i.test(t)) message = 'Signing in...';
                else if (t.length > 0 && t.length < 40) message = t + '...';
                btn.classList.add('opacity-60', 'pointer-events-none');
            }
            var overlay = document.getElementById('formLoadingOverlay');
            var text = document.getElementById('formLoadingText');
            if (overlay && text) { text.textContent = message; overlay.classList.remove('hidden'); overlay.classList.add('flex'); }
        });

        function hideOverlay() {
            var overlay = document.getElementById('formLoadingOverlay');
            if (overlay) { overlay.classList.add('hidden'); overlay.classList.remove('flex'); }
            document.querySelectorAll('button.opacity-60').forEach(function(b) { b.classList.remove('opacity-60', 'pointer-events-none'); });
        }
        document.addEventListener('turbo:load', hideOverlay);
        document.addEventListener('turbo:fetch-request-error', hideOverlay);

        // Full page reload on server errors so users never see stale/cached content
        document.addEventListener('turbo:frame-missing', function(e) { e.preventDefault(); window.location.href = e.detail.response.url || window.location.href; });
        document.addEventListener('turbo:visit', function(e) {
            if (e.detail && e.detail.action === 'restore') return;
        });
        document.addEventListener('turbo:before-render', function(e) {
            var newBody = e.detail.newBody || (e.detail.newDocument && e.detail.newDocument.body);
            if (!newBody || newBody.querySelector('[data-turbo-error]')) {
                e.preventDefault();
                window.location.reload();
            }
        });
    }
    window.addEventListener('pageshow', function() {
        var overlay = document.getElementById('formLoadingOverlay');
        if (overlay) { overlay.classList.add('hidden'); overlay.classList.remove('flex'); }
    });
</script>
<style>
    .user-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0) scale(1); }
</style>
@stack('scripts')
</body>
</html>
