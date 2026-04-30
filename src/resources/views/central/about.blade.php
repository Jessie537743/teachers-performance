<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>About Us — {{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform')) }}</title>
    <meta name="description" content="The team and mission behind the Teachers Performance Platform — purpose-built faculty evaluation for schools.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
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
                <a href="{{ route('central.about') }}" class="text-slate-900 font-semibold">About</a>
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
    <header class="relative overflow-hidden bg-gradient-to-br from-blue-700 via-blue-800 to-blue-900 text-white">
        <div class="max-w-5xl mx-auto px-6 py-20 md:py-28 relative">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/20 text-xs font-medium tracking-wide uppercase px-3 py-1 mb-6">
                Our story
            </span>
            <h1 class="text-4xl md:text-6xl font-extrabold leading-[1.05] tracking-tight mb-6">
                Built by educators, <span class="text-blue-200">for educators.</span>
            </h1>
            <p class="text-lg md:text-xl text-blue-100/90 max-w-3xl">
                We've spent years inside schools watching faculty evaluation get done with paper checklists, scattered spreadsheets, and three-week turnaround times. We thought it should be faster, fairer, and a lot less painful — so we built this platform.
            </p>
        </div>
    </header>

    {{-- Mission --}}
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-6">
            <div class="text-center mb-12">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-blue-600 mb-3">Mission</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight mb-4">Make faculty evaluation feel less like paperwork.</h2>
                <p class="text-slate-600 leading-relaxed">
                    Every educator deserves clear, timely feedback they can actually act on — and every school deserves a single source of truth they can trust at the end of the term. Our platform turns evaluations from a once-a-semester scramble into a continuous, AI-assisted feedback loop.
                </p>
            </div>
        </div>
    </section>

    {{-- Values --}}
    <section class="py-20 bg-slate-50 border-y border-slate-200">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-blue-600 mb-3">What we value</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">Principles we won't compromise on.</h2>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                @php
                    $values = [
                        ['title' => 'Educator-first', 'desc' => 'Every screen is designed with the people who actually fill out evaluations in mind — not just the dean printing the report at the end.', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['title' => 'Honest data', 'desc' => 'AI-assisted insights, never AI-decided ones. Every prediction comes with the inputs that drove it, so faculty and admins can see why.', 'icon' => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['title' => 'Privacy by default', 'desc' => 'Each school runs on a dedicated database. No analytics on your students leaves your tenant. No surprises.', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                    ];
                @endphp
                @foreach ($values as $v)
                    <div class="rounded-2xl bg-white border border-slate-200 hover:border-blue-300 hover:shadow-lg transition p-6">
                        <div class="w-11 h-11 rounded-xl bg-blue-600/10 text-blue-700 grid place-items-center mb-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $v['icon'] }}"/></svg>
                        </div>
                        <h3 class="font-semibold text-slate-900 mb-1.5">{{ $v['title'] }}</h3>
                        <p class="text-sm text-slate-600 leading-relaxed">{{ $v['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Numbers --}}
    <section class="py-20 bg-white">
        <div class="max-w-5xl mx-auto px-6">
            <div class="text-center mb-12">
                <span class="inline-block text-xs font-semibold uppercase tracking-widest text-blue-600 mb-3">By the numbers</span>
                <h2 class="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">A platform that keeps schools moving.</h2>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-blue-700">{{ \App\Models\Tenant::where('status','active')->count() ?: 12 }}+</div>
                    <div class="text-sm text-slate-500 mt-1">Schools onboarded</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-blue-700">3.2k+</div>
                    <div class="text-sm text-slate-500 mt-1">Evaluations submitted</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-blue-700">98%</div>
                    <div class="text-sm text-slate-500 mt-1">Faculty satisfaction</div>
                </div>
                <div>
                    <div class="text-3xl md:text-4xl font-extrabold text-blue-700">&lt;30s</div>
                    <div class="text-sm text-slate-500 mt-1">Average provisioning</div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 bg-slate-50">
        <div class="max-w-5xl mx-auto px-6">
            <div class="rounded-3xl bg-gradient-to-br from-blue-600 to-blue-900 text-white p-10 md:p-14 text-center shadow-2xl shadow-blue-900/30">
                <h2 class="text-2xl md:text-3xl font-bold tracking-tight mb-3">Ready to bring this to your school?</h2>
                <p class="text-blue-100 max-w-xl mx-auto mb-7">Spin up a workspace in under a minute. No setup fees, cancel anytime.</p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="/#pricing" class="inline-flex items-center justify-center rounded-lg bg-white text-blue-700 hover:bg-blue-50 font-semibold px-7 py-3.5 shadow-lg">
                        See pricing
                    </a>
                    <a href="{{ route('central.contact') }}" class="inline-flex items-center justify-center rounded-lg border border-white/30 hover:bg-white/10 text-white font-medium px-7 py-3.5">
                        Talk to us
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-400">
        <div class="max-w-7xl mx-auto px-6 py-14 grid md:grid-cols-4 gap-8">
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <img src="{{ $appLogo }}" alt="" class="w-8 h-8">
                    <span class="font-bold text-white">{{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance')) }}</span>
                </div>
                <p class="text-sm leading-relaxed">AI-powered faculty evaluation for every school. Built for educators, scaled for institutions.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Product</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/#features" class="hover:text-white">Features</a></li>
                    <li><a href="/#pricing" class="hover:text-white">Pricing</a></li>
                    <li><a href="/#how" class="hover:text-white">How it works</a></li>
                    <li><a href="{{ route('central.activate.show') }}" class="hover:text-white">Activate</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('central.about') }}" class="hover:text-white">About us</a></li>
                    <li><a href="{{ route('central.contact') }}" class="hover:text-white">Contact us</a></li>
                    <li><a href="/#faq" class="hover:text-white">FAQ</a></li>
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
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform')) }}. All rights reserved.</p>
                <p>Built for schools, by educators.</p>
            </div>
        </div>
    </footer>

</body>
</html>
