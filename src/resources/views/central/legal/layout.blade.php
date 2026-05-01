<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} — {{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform')) }}</title>
    <meta name="description" content="{{ $pageDescription ?? 'Legal information for the Teachers Performance Platform.' }}">
    <meta name="robots" content="index,follow">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, sans-serif; }
        .legal-prose h2 { font-size: 1.25rem; font-weight: 700; color: #0f172a; margin-top: 2.25rem; margin-bottom: 0.75rem; letter-spacing: -0.01em; }
        .legal-prose h3 { font-size: 1rem; font-weight: 600; color: #0f172a; margin-top: 1.5rem; margin-bottom: 0.5rem; }
        .legal-prose p, .legal-prose li { color: #334155; line-height: 1.7; font-size: 0.9375rem; }
        .legal-prose p { margin-bottom: 0.875rem; }
        .legal-prose ul { list-style: disc; padding-left: 1.5rem; margin-bottom: 0.875rem; }
        .legal-prose ul li { margin-bottom: 0.375rem; }
        .legal-prose a { color: #2563eb; text-decoration: underline; }
        .legal-prose a:hover { color: #1d4ed8; }
        .legal-prose strong { color: #0f172a; font-weight: 600; }
        .legal-prose code { background: #f1f5f9; padding: 0.125rem 0.375rem; border-radius: 0.25rem; font-size: 0.8125rem; }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased">

    {{-- Top Nav --}}
    <nav class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b border-slate-200/70">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ $appLogo }}" alt="" class="w-8 h-8">
                <span class="font-bold text-slate-900 tracking-tight">{{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance')) }}</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="/#features" class="text-slate-600 hover:text-slate-900">Features</a>
                <a href="/#how" class="text-slate-600 hover:text-slate-900">How it works</a>
                <a href="/#pricing" class="text-slate-600 hover:text-slate-900">Pricing</a>
                <a href="{{ route('central.about') }}" class="text-slate-600 hover:text-slate-900">About</a>
                <a href="{{ route('central.contact') }}" class="text-slate-600 hover:text-slate-900">Contact</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('central.activate.show') }}" class="hidden sm:inline-flex text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Activate</a>
                <a href="/#pricing" class="inline-flex items-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 shadow-sm">
                    Get started
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <header class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-white border-b border-slate-200">
        <div class="max-w-4xl mx-auto px-6 py-16 md:py-20">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/20 text-xs font-medium tracking-wide uppercase px-3 py-1 mb-5">
                Legal
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold leading-tight tracking-tight mb-3">{{ $pageTitle }}</h1>
            <p class="text-sm md:text-base text-slate-300">Last updated: {{ $lastUpdated ?? 'May 1, 2026' }}</p>
        </div>
    </header>

    {{-- Content --}}
    <main class="max-w-4xl mx-auto px-6 py-12 md:py-16">
        <div class="grid md:grid-cols-[200px_1fr] gap-10">
            {{-- Side nav --}}
            <aside class="hidden md:block">
                <div class="sticky top-24">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">Legal</p>
                    <nav class="flex flex-col gap-1.5 text-sm">
                        <a href="{{ route('central.terms') }}" class="{{ request()->routeIs('central.terms') ? 'text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Terms of Service</a>
                        <a href="{{ route('central.privacy') }}" class="{{ request()->routeIs('central.privacy') ? 'text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Privacy Policy</a>
                        <a href="{{ route('central.data-processing') }}" class="{{ request()->routeIs('central.data-processing') ? 'text-blue-700 font-semibold' : 'text-slate-600 hover:text-slate-900' }}">Data Processing</a>
                    </nav>
                </div>
            </aside>

            <article class="legal-prose min-w-0">
                {{ $slot ?? '' }}
                @yield('legal')
            </article>
        </div>
    </main>

    @include('central.partials.footer-mini')

</body>
</html>
