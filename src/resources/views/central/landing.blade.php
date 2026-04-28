<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teachers Performance Platform — AI-powered faculty evaluation for every school</title>
    <meta name="description" content="Multi-school faculty evaluation, peer review, and AI-powered performance insights. One console for every campus.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', system-ui, -apple-system, sans-serif; }
        .gradient-hero {
            background:
                radial-gradient(1200px 600px at 10% 0%, rgba(96,165,250,0.35), transparent 60%),
                radial-gradient(900px 500px at 90% 20%, rgba(59,130,246,0.30), transparent 60%),
                linear-gradient(135deg, #1e3a8a 0%, #1e40af 40%, #2563eb 100%);
        }
        .gradient-text {
            background: linear-gradient(90deg, #93c5fd, #ffffff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        .blob {
            position: absolute;
            border-radius: 9999px;
            background: rgba(255,255,255,0.06);
            filter: blur(2px);
            pointer-events: none;
        }
        @keyframes floatY { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-14px); } }
        @keyframes floatYSlow { 0%,100% { transform: translateY(0); } 50% { transform: translateY(-22px); } }
        @keyframes fadeInUp { from { opacity:0; transform: translateY(16px); } to { opacity:1; transform: none; } }
        .anim-float { animation: floatY 5s ease-in-out infinite; }
        .anim-float-slow { animation: floatYSlow 7s ease-in-out infinite; }
        .anim-in { animation: fadeInUp 0.7s ease-out both; }
        .grid-bg {
            background-image:
                linear-gradient(rgba(15,23,42,0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(15,23,42,0.06) 1px, transparent 1px);
            background-size: 32px 32px;
        }
    </style>
</head>
<body class="bg-white text-slate-800 antialiased">

    {{-- Top Nav --}}
    <nav class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b border-slate-200/70">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ asset(config('app.default_logo')) }}" alt="" class="w-8 h-8">
                <span class="font-bold text-slate-900 tracking-tight">{{ config('app.name', 'Teachers Performance') }}</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="#features" class="text-slate-600 hover:text-slate-900">Features</a>
                <a href="#how" class="text-slate-600 hover:text-slate-900">How it works</a>
                <a href="#pricing" class="text-slate-600 hover:text-slate-900">Pricing</a>
                <a href="#faq" class="text-slate-600 hover:text-slate-900">FAQ</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('central.activate.show') }}" class="hidden sm:inline-flex text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Activate</a>
                <a href="#pricing" class="inline-flex items-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 shadow-sm">
                    Get started
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <header class="relative overflow-hidden gradient-hero text-white">
        <div class="blob w-72 h-72 -top-20 -left-20 anim-float-slow"></div>
        <div class="blob w-96 h-96 top-10 right-0 anim-float"></div>
        <div class="blob w-40 h-40 bottom-10 left-1/3 anim-float-slow" style="animation-delay:1s"></div>

        <div class="max-w-7xl mx-auto px-6 pt-20 pb-24 md:pt-28 md:pb-32 relative">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="anim-in">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/20 text-xs font-medium tracking-wide uppercase px-3 py-1 mb-6">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        New • AI performance insights
                    </span>
                    <h1 class="text-4xl md:text-6xl font-extrabold leading-[1.05] tracking-tight mb-6">
                        Smarter faculty evaluation for <span class="gradient-text">every campus</span>.
                    </h1>
                    <p class="text-lg md:text-xl text-blue-100/90 mb-8 max-w-xl">
                        Run peer, dean, self, and student evaluations from one console. Get AI-powered performance predictions, sentiment analysis, and a dedicated workspace per school.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="#pricing" class="inline-flex items-center justify-center rounded-lg bg-white text-blue-700 hover:bg-blue-50 font-semibold px-6 py-3 shadow-lg shadow-blue-900/30">
                            Start your school
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="{{ route('central.activate.show') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 hover:bg-white/10 text-white font-medium px-6 py-3">
                            I have an activation code
                        </a>
                    </div>
                    <div class="mt-8 flex items-center gap-6 text-blue-100/80 text-sm">
                        <div class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4L9 11.6l6.3-6.3a1 1 0 011.4 0z"/></svg> No setup fees</div>
                        <div class="flex items-center gap-2"><svg class="w-4 h-4 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4L9 11.6l6.3-6.3a1 1 0 011.4 0z"/></svg> Cancel anytime</div>
                        <div class="hidden sm:flex items-center gap-2"><svg class="w-4 h-4 text-emerald-300" fill="currentColor" viewBox="0 0 20 20"><path d="M16.7 5.3a1 1 0 010 1.4l-7 7a1 1 0 01-1.4 0l-3-3a1 1 0 111.4-1.4L9 11.6l6.3-6.3a1 1 0 011.4 0z"/></svg> Dedicated DB per school</div>
                    </div>
                </div>

                {{-- Right: app mockup --}}
                <div class="relative anim-in" style="animation-delay:0.15s">
                    <div class="absolute -inset-4 bg-blue-500/20 rounded-3xl blur-2xl"></div>
                    <div class="relative bg-slate-900/60 backdrop-blur ring-1 ring-white/10 rounded-2xl p-3 shadow-2xl">
                        <div class="flex items-center gap-1.5 px-2 py-2">
                            <span class="w-3 h-3 rounded-full bg-red-400/80"></span>
                            <span class="w-3 h-3 rounded-full bg-yellow-300/80"></span>
                            <span class="w-3 h-3 rounded-full bg-green-400/80"></span>
                            <span class="ml-3 text-xs text-blue-200/70 font-mono truncate">yourschool.{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'platform.test' }}/dashboard</span>
                        </div>
                        <div class="bg-white rounded-xl overflow-hidden">
                            <div class="grid grid-cols-4">
                                <aside class="bg-slate-50 border-r border-slate-200 p-4 space-y-2 hidden sm:block">
                                    <div class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-2">Workspace</div>
                                    <div class="rounded-md bg-blue-600 text-white text-xs font-medium px-2 py-1.5">Dashboard</div>
                                    <div class="rounded-md text-slate-600 text-xs px-2 py-1.5">Evaluations</div>
                                    <div class="rounded-md text-slate-600 text-xs px-2 py-1.5">Faculty</div>
                                    <div class="rounded-md text-slate-600 text-xs px-2 py-1.5">Reports</div>
                                    <div class="rounded-md text-slate-600 text-xs px-2 py-1.5">Settings</div>
                                </aside>
                                <main class="col-span-4 sm:col-span-3 p-5 space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <div class="text-xs text-slate-500">Evaluation period</div>
                                            <div class="text-sm font-semibold text-slate-900">Sem 2 · 2026</div>
                                        </div>
                                        <div class="text-xs text-emerald-700 bg-emerald-50 ring-1 ring-emerald-200 rounded-full px-2 py-0.5">+12% vs last sem</div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-2">
                                        <div class="rounded-lg bg-slate-50 p-3">
                                            <div class="text-[10px] text-slate-500 uppercase">Submitted</div>
                                            <div class="text-base font-bold text-slate-900">412</div>
                                        </div>
                                        <div class="rounded-lg bg-blue-50 p-3">
                                            <div class="text-[10px] text-blue-600 uppercase">Avg score</div>
                                            <div class="text-base font-bold text-blue-700">4.32</div>
                                        </div>
                                        <div class="rounded-lg bg-amber-50 p-3">
                                            <div class="text-[10px] text-amber-700 uppercase">Pending</div>
                                            <div class="text-base font-bold text-amber-700">28</div>
                                        </div>
                                    </div>
                                    <div class="rounded-lg ring-1 ring-slate-200 p-3">
                                        <div class="text-xs font-semibold text-slate-700 mb-2">Sentiment trend</div>
                                        <svg viewBox="0 0 280 60" class="w-full h-12">
                                            <defs>
                                                <linearGradient id="lg" x1="0" y1="0" x2="0" y2="1">
                                                    <stop offset="0%" stop-color="#3b82f6" stop-opacity="0.4"/>
                                                    <stop offset="100%" stop-color="#3b82f6" stop-opacity="0"/>
                                                </linearGradient>
                                            </defs>
                                            <path d="M0,45 L25,40 L55,42 L85,30 L115,32 L145,22 L175,25 L205,15 L235,18 L265,8 L280,10 L280,60 L0,60 Z" fill="url(#lg)"/>
                                            <path d="M0,45 L25,40 L55,42 L85,30 L115,32 L145,22 L175,25 L205,15 L235,18 L265,8 L280,10" fill="none" stroke="#2563eb" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </div>
                                </main>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <svg viewBox="0 0 1440 80" class="block w-full" preserveAspectRatio="none" aria-hidden="true">
            <path d="M0,80 L1440,80 L1440,40 C1080,80 720,0 360,40 L0,80 Z" fill="white"/>
        </svg>
    </header>

    {{-- Social proof bar --}}
    <section class="py-10 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <p class="text-center text-xs font-semibold uppercase tracking-widest text-slate-500 mb-6">Trusted by schools running real evaluations</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-6 items-center">
                @foreach (['St. Mary\'s Academy', 'JCD College', 'Riverside Institute', 'Northgate University'] as $school)
                    <div class="flex items-center justify-center gap-2 text-slate-400 hover:text-slate-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10l9-7 9 7-9 7-9-7zm0 0v8a2 2 0 002 2h14a2 2 0 002-2v-8"/></svg>
                        <span class="text-sm font-semibold tracking-tight">{{ $school }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Stats strip --}}
    <section class="bg-slate-50 border-y border-slate-200 py-12">
        <div class="max-w-6xl mx-auto px-6 grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl font-extrabold text-blue-700">{{ \App\Models\Tenant::where('status','active')->count() ?: 12 }}+</div>
                <div class="text-sm text-slate-500 mt-1">Schools onboarded</div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-blue-700">3.2k+</div>
                <div class="text-sm text-slate-500 mt-1">Evaluations submitted</div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-blue-700">98%</div>
                <div class="text-sm text-slate-500 mt-1">Faculty satisfaction</div>
            </div>
            <div>
                <div class="text-3xl font-extrabold text-blue-700">&lt;30s</div>
                <div class="text-sm text-slate-500 mt-1">Average provisioning time</div>
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="py-24 bg-white relative">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-blue-600 mb-3">Everything you need</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight mb-4">A complete evaluation toolkit, end to end.</h2>
                <p class="text-slate-600">From running peer reviews to predicting at-risk faculty, every workflow lives in one place — purpose-built for schools.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @php
                    $features = [
                        ['title' => 'Multi-evaluator types', 'desc' => 'Peer, dean, self, and student evaluations with role-based questions and weighted scoring.', 'icon' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-5.13a4 4 0 11-8 0 4 4 0 018 0zm6 0a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['title' => 'AI performance predictions', 'desc' => 'Trained models flag at-risk faculty before the term ends, with feature-importance explanations.', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
                        ['title' => 'Sentiment analysis', 'desc' => 'Free-text feedback is auto-classified — quickly spot themes across hundreds of responses.', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['title' => 'Per-school workspace', 'desc' => 'Each tenant gets its own database, branding, and admin console — fully isolated.', 'icon' => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2H7a2 2 0 00-2 2v2'],
                        ['title' => 'Announcements & comms', 'desc' => 'Targeted announcements with read receipts, scoped to roles, departments, or everyone.', 'icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z'],
                        ['title' => 'Audit trail & exports', 'desc' => 'Every action logged. Export PDF reports, CSV scores, and analytical summaries on demand.', 'icon' => 'M9 17v-2a4 4 0 014-4h4M3 7l3-3 3 3M3 7v10a4 4 0 004 4h10'],
                    ];
                @endphp
                @foreach ($features as $feat)
                    <div class="group relative rounded-2xl bg-white border border-slate-200 hover:border-blue-300 hover:shadow-xl hover:shadow-blue-900/5 transition p-6">
                        <div class="w-11 h-11 rounded-xl bg-blue-600/10 text-blue-700 grid place-items-center mb-4 group-hover:bg-blue-600 group-hover:text-white transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $feat['icon'] }}"/></svg>
                        </div>
                        <h3 class="font-semibold text-slate-900 mb-1">{{ $feat['title'] }}</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">{{ $feat['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how" class="bg-slate-50 py-24 relative overflow-hidden">
        <div class="absolute inset-0 grid-bg opacity-50"></div>
        <div class="max-w-6xl mx-auto px-6 relative">
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-blue-600 mb-3">From signup to sign-in</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight mb-4">Live in under five minutes.</h2>
                <p class="text-slate-600">Pick a plan, fill in your school details, and we provision a dedicated workspace. Your activation code arrives by email.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @php
                    $steps = [
                        ['n' => '01', 'title' => 'Choose your plan', 'desc' => 'Free, Pro, or Enterprise — pick what fits your school size and feature needs.'],
                        ['n' => '02', 'title' => 'Subscribe in seconds', 'desc' => 'Enter your school name, subdomain, and admin email. Pay (simulated in demo) and we provision instantly.'],
                        ['n' => '03', 'title' => 'Activate & sign in', 'desc' => 'Use the activation code we email you, set your password, and start running evaluations.'],
                    ];
                @endphp
                @foreach ($steps as $i => $step)
                    <div class="relative rounded-2xl bg-white border border-slate-200 p-7 shadow-sm">
                        <div class="text-5xl font-extrabold text-blue-600/10 absolute top-4 right-5 select-none">{{ $step['n'] }}</div>
                        <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-blue-600 to-blue-800 text-white font-bold grid place-items-center mb-4 shadow-md">{{ $i + 1 }}</div>
                        <h3 class="font-semibold text-slate-900 mb-2">{{ $step['title'] }}</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="py-24 bg-white">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center max-w-2xl mx-auto mb-10">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-blue-600 mb-3">Simple, transparent pricing</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight mb-4">Plans that scale with your school.</h2>
                <p class="text-slate-600">Switch or upgrade anytime. No setup fees. Cancel whenever.</p>
            </div>

            {{-- Billing cycle toggle --}}
            <div class="flex justify-center mb-10">
                <div class="inline-flex items-center gap-1 p-1 rounded-full bg-slate-100 ring-1 ring-slate-200">
                    <button type="button" data-cycle="monthly" class="cycle-btn px-5 py-2 rounded-full text-sm font-semibold bg-white text-slate-900 shadow-sm transition">Monthly</button>
                    <button type="button" data-cycle="yearly"  class="cycle-btn px-5 py-2 rounded-full text-sm font-semibold text-slate-600 hover:text-slate-900 transition inline-flex items-center gap-2">
                        Yearly
                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Save 17%</span>
                    </button>
                </div>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach (config('plans') as $slug => $plan)
                    @php
                        $monthlyPrice = $plan['prices']['monthly'] ?? null;
                        $yearlyPrice  = $plan['prices']['yearly']  ?? null;
                        $isCustom     = ! is_numeric($monthlyPrice);
                    @endphp
                    <div class="relative rounded-2xl bg-white p-8 flex flex-col transition
                                {{ $plan['highlight']
                                    ? 'border-2 border-blue-600 shadow-xl shadow-blue-900/10 -translate-y-1'
                                    : 'border border-slate-200 hover:border-slate-300 hover:shadow-lg' }}">
                        @if ($plan['highlight'])
                            <span class="absolute -top-3.5 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-blue-600 to-blue-800 px-3 py-1 text-xs font-semibold text-white shadow-md">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.367 2.446a1 1 0 00-.364 1.118l1.287 3.957c.3.922-.755 1.688-1.539 1.118l-3.366-2.446a1 1 0 00-1.176 0l-3.367 2.446c-.783.57-1.838-.197-1.539-1.118l1.287-3.957a1 1 0 00-.364-1.118L2.05 9.384c-.783-.57-.38-1.81.588-1.81h4.163a1 1 0 00.95-.69l1.287-3.957z"/></svg>
                                Most popular
                            </span>
                        @endif

                        <h3 class="text-xl font-bold text-slate-900">{{ $plan['name'] }}</h3>
                        <p class="text-sm text-slate-500 mt-1 mb-5">{{ $plan['tagline'] }}</p>

                        <div class="mb-6">
                            @if ($isCustom)
                                <span class="text-5xl font-extrabold tracking-tight text-slate-900">Custom</span>
                            @else
                                <span class="text-5xl font-extrabold tracking-tight text-slate-900">
                                    $<span class="price-amount" data-monthly="{{ $monthlyPrice }}" data-yearly="{{ $yearlyPrice }}">{{ $monthlyPrice }}</span>
                                </span>
                                <span class="text-slate-500 text-sm ml-1 price-period">@if((int)$monthlyPrice===0)forever @else per month @endif</span>
                            @endif
                        </div>

                        <ul class="space-y-3 text-sm text-slate-700 mb-8 flex-1">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-start gap-3">
                                    <span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-50 text-blue-600 grid place-items-center mt-0.5">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    </span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        @if ($slug === 'enterprise' || $isCustom)
                            <a href="mailto:sales@platform.test?subject=Interested in the {{ $plan['name'] }} plan"
                               class="inline-flex items-center justify-center rounded-lg border border-slate-300 text-slate-700 hover:bg-slate-50 px-4 py-3 text-sm font-semibold transition">
                                Contact sales
                            </a>
                        @else
                            <a href="{{ route('central.subscribe.show', ['plan' => $slug, 'cycle' => 'monthly']) }}"
                               data-monthly-href="{{ route('central.subscribe.show', ['plan' => $slug, 'cycle' => 'monthly']) }}"
                               data-yearly-href="{{ route('central.subscribe.show', ['plan' => $slug, 'cycle' => 'yearly']) }}"
                               class="plan-cta inline-flex items-center justify-center rounded-lg px-4 py-3 text-sm font-semibold transition
                                      {{ $plan['highlight']
                                          ? 'bg-gradient-to-r from-blue-600 to-blue-800 text-white hover:from-blue-700 hover:to-blue-900 shadow-md'
                                          : 'bg-slate-900 text-white hover:bg-slate-800' }}">
                                Get started
                                <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                            </a>
                        @endif
                    </div>
                @endforeach
            </div>

            <p class="text-center text-xs text-slate-500 mt-8">
                All plans include encrypted data, daily backups, and 99.9% uptime SLA.
            </p>
        </div>

        <script>
            (function () {
                const buttons = document.querySelectorAll('.cycle-btn');
                let activeCycle = 'monthly';

                function setCycle(cycle) {
                    activeCycle = cycle;
                    buttons.forEach(b => {
                        const isActive = b.dataset.cycle === cycle;
                        b.classList.toggle('bg-white', isActive);
                        b.classList.toggle('text-slate-900', isActive);
                        b.classList.toggle('shadow-sm', isActive);
                        b.classList.toggle('text-slate-600', !isActive);
                    });
                    document.querySelectorAll('.price-amount').forEach(el => {
                        const v = el.dataset[cycle];
                        if (v != null) el.textContent = v;
                    });
                    document.querySelectorAll('.price-period').forEach(el => {
                        const isFree = el.parentElement.querySelector('.price-amount')?.textContent === '0';
                        if (isFree) { el.textContent = 'forever'; return; }
                        el.textContent = cycle === 'yearly' ? 'per year' : 'per month';
                    });
                    document.querySelectorAll('.plan-cta').forEach(a => {
                        const href = a.dataset[cycle + 'Href'];
                        if (href) a.setAttribute('href', href);
                    });
                }
                buttons.forEach(b => b.addEventListener('click', () => setCycle(b.dataset.cycle)));
            })();
        </script>
    </section>

    {{-- Testimonial --}}
    <section class="py-24 bg-gradient-to-br from-blue-700 to-blue-900 text-white relative overflow-hidden">
        <div class="blob w-72 h-72 -top-20 right-10 anim-float-slow"></div>
        <div class="max-w-4xl mx-auto px-6 text-center relative">
            <svg class="w-12 h-12 mx-auto mb-6 text-blue-300/60" fill="currentColor" viewBox="0 0 24 24"><path d="M9.983 3v7.391c0 5.704-3.731 9.57-8.983 10.609l-.995-2.151c2.432-.917 3.995-3.638 3.995-5.849h-4v-10h9.983zm14.017 0v7.391c0 5.704-3.748 9.571-9 10.609l-.996-2.151c2.433-.917 3.996-3.638 3.996-5.849h-3.983v-10h9.983z"/></svg>
            <p class="text-2xl md:text-3xl font-medium leading-relaxed mb-8 text-blue-50">
                "We replaced three separate evaluation tools with this. The AI performance predictions caught two faculty members who needed mentoring before our annual review — and the per-school workspace meant zero IT involvement."
            </p>
            <div class="flex items-center justify-center gap-4">
                <div class="w-12 h-12 rounded-full bg-blue-300/30 ring-2 ring-white/30 grid place-items-center font-bold text-lg">DR</div>
                <div class="text-left">
                    <div class="font-semibold">Dr. Rosario Mendez</div>
                    <div class="text-sm text-blue-200">Dean of Faculty, JCD College</div>
                </div>
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="py-24 bg-white">
        <div class="max-w-3xl mx-auto px-6">
            <div class="text-center mb-12">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-blue-600 mb-3">Questions</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">Frequently asked</h2>
            </div>

            @php
                $faqs = [
                    ['q' => 'What does "dedicated DB per school" mean?', 'a' => 'Every tenant gets its own isolated MySQL database. Your data never mixes with another school\'s, and we can restore or migrate your school independently.'],
                    ['q' => 'How long does provisioning take?', 'a' => 'Under 30 seconds in most cases. Once payment confirms, we run migrations, seed your template data, mint an activation code, and email it to your admin.'],
                    ['q' => 'Can I customize the branding?', 'a' => 'Yes. Pro and Enterprise plans let you upload your school logo, set your colors, and customize email templates from the in-app Settings page.'],
                    ['q' => 'Is the AI prediction accurate?', 'a' => 'Our model trains on aggregated, anonymized evaluation patterns. Accuracy varies by signal strength — it\'s a decision-support tool, not a verdict. Feature-importance views show what drove each prediction.'],
                    ['q' => 'What happens if we cancel?', 'a' => 'You can export all your data as CSV or PDF before cancellation. We retain your tenant DB for 30 days post-cancellation in case you change your mind, then permanently delete it.'],
                ];
            @endphp

            <div class="divide-y divide-slate-200 border-t border-b border-slate-200">
                @foreach ($faqs as $faq)
                    <details class="group py-5">
                        <summary class="flex items-center justify-between cursor-pointer list-none">
                            <span class="font-semibold text-slate-900 pr-6">{{ $faq['q'] }}</span>
                            <span class="flex-shrink-0 w-8 h-8 rounded-full bg-slate-100 grid place-items-center group-open:bg-blue-600 group-open:text-white transition">
                                <svg class="w-4 h-4 transition group-open:rotate-45" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                            </span>
                        </summary>
                        <p class="mt-3 text-slate-600 leading-relaxed text-sm">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="py-20 bg-slate-50">
        <div class="max-w-5xl mx-auto px-6">
            <div class="rounded-3xl bg-gradient-to-br from-blue-600 to-blue-900 text-white p-10 md:p-14 text-center relative overflow-hidden shadow-2xl shadow-blue-900/30">
                <div class="blob w-64 h-64 -top-16 -left-16 anim-float-slow"></div>
                <div class="blob w-72 h-72 -bottom-16 -right-16 anim-float"></div>
                <div class="relative">
                    <h2 class="text-3xl md:text-4xl font-bold tracking-tight mb-3">Ready to modernize faculty evaluation?</h2>
                    <p class="text-blue-100 max-w-xl mx-auto mb-7">Spin up your school's workspace today. Activation arrives in your inbox in under a minute.</p>
                    <div class="flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="#pricing" class="inline-flex items-center justify-center rounded-lg bg-white text-blue-700 hover:bg-blue-50 font-semibold px-7 py-3.5 shadow-lg">
                            Start your school
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </a>
                        <a href="{{ route('central.activate.show') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 hover:bg-white/10 text-white font-medium px-7 py-3.5">
                            I have a code
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-400">
        <div class="max-w-7xl mx-auto px-6 py-14 grid md:grid-cols-4 gap-8">
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <img src="{{ asset(config('app.default_logo')) }}" alt="" class="w-8 h-8">
                    <span class="font-bold text-white">{{ config('app.name', 'Teachers Performance') }}</span>
                </div>
                <p class="text-sm leading-relaxed">AI-powered faculty evaluation for every school. Built for educators, scaled for institutions.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Product</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#features" class="hover:text-white">Features</a></li>
                    <li><a href="#pricing" class="hover:text-white">Pricing</a></li>
                    <li><a href="#how" class="hover:text-white">How it works</a></li>
                    <li><a href="{{ route('central.activate.show') }}" class="hover:text-white">Activate</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="mailto:sales@platform.test" class="hover:text-white">Contact sales</a></li>
                    <li><a href="mailto:support@platform.test" class="hover:text-white">Support</a></li>
                    <li><a href="#faq" class="hover:text-white">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-white">Privacy policy</a></li>
                    <li><a href="#" class="hover:text-white">Terms of service</a></li>
                    <li><a href="#" class="hover:text-white">Data processing</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Teachers Performance Platform') }}. All rights reserved.</p>
                <p>Built for schools, by educators.</p>
            </div>
        </div>
    </footer>

</body>
</html>
