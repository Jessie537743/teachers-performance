<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Teachers Performance — Multi-tenant evaluation platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-800">

    {{-- Hero --}}
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-6 py-20 text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-slate-900 mb-4">Teachers Performance Platform</h1>
            <p class="text-lg text-slate-600 mb-8 max-w-2xl mx-auto">
                Multi-school faculty evaluation, peer review, and AI-powered performance insights — one console for every campus.
            </p>
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                <a href="{{ route('central.activate.show') }}" class="inline-flex items-center rounded-md bg-slate-900 px-6 py-3 text-sm font-medium text-white hover:bg-slate-800">
                    Got an activation code? → Activate
                </a>
                <a href="mailto:sales@platform.test" class="inline-flex items-center rounded-md border border-slate-300 px-6 py-3 text-sm text-slate-700 hover:bg-slate-50">
                    Contact sales
                </a>
            </div>
        </div>
    </header>

    {{-- Pricing --}}
    <section class="py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-semibold text-slate-900 mb-2">Plans for every school</h2>
                <p class="text-slate-600">Simple tiers. Switch or upgrade anytime.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                @foreach (config('plans') as $slug => $plan)
                    <div class="relative bg-white rounded-lg shadow border-2 {{ $plan['highlight'] ? 'border-slate-900' : 'border-transparent' }} p-8 flex flex-col">
                        @if ($plan['highlight'])
                            <span class="absolute -top-3 left-1/2 -translate-x-1/2 inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-medium text-white">Most popular</span>
                        @endif

                        <h3 class="text-xl font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                        <p class="text-sm text-slate-500 mt-1 mb-4">{{ $plan['tagline'] }}</p>

                        <div class="mb-6">
                            @if (is_numeric($plan['price']))
                                <span class="text-4xl font-bold text-slate-900">${{ $plan['price'] }}</span>
                                <span class="text-slate-500 text-sm ml-1">{{ $plan['period'] }}</span>
                            @else
                                <span class="text-4xl font-bold text-slate-900">{{ $plan['price'] }}</span>
                            @endif
                        </div>

                        <ul class="space-y-2 text-sm text-slate-700 mb-8 flex-1">
                            @foreach ($plan['features'] as $feature)
                                <li class="flex items-start gap-2">
                                    <span class="text-green-600 flex-shrink-0">✓</span>
                                    <span>{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <a href="mailto:sales@platform.test?subject=Interested in the {{ $plan['name'] }} plan"
                           class="inline-flex items-center justify-center rounded-md {{ $plan['highlight'] ? 'bg-slate-900 text-white hover:bg-slate-800' : 'border border-slate-300 text-slate-700 hover:bg-slate-50' }} px-4 py-2 text-sm font-medium">
                            Get started — contact sales
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="bg-white border-y border-slate-200 py-20">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-semibold text-slate-900 mb-2">How onboarding works</h2>
                <p class="text-slate-600">Sales-led, fast to spin up.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center mx-auto mb-4">1</div>
                    <h3 class="font-semibold text-slate-900 mb-1">We provision your school</h3>
                    <p class="text-sm text-slate-600">A dedicated tenant database with the full schema, ready in seconds.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center mx-auto mb-4">2</div>
                    <h3 class="font-semibold text-slate-900 mb-1">You receive your activation code</h3>
                    <p class="text-sm text-slate-600">A one-time 12-char code, valid for 30 days, sent to your school's first admin.</p>
                </div>
                <div class="text-center">
                    <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-700 font-semibold flex items-center justify-center mx-auto mb-4">3</div>
                    <h3 class="font-semibold text-slate-900 mb-1">Sign in with your chosen password</h3>
                    <p class="text-sm text-slate-600">Redeem the code, set your own password, and you're in.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="py-8 text-center text-sm text-slate-500">
        <p>&copy; {{ date('Y') }} Teachers Performance Platform.
        <a href="{{ route('central.activate.show') }}" class="text-slate-700 hover:text-slate-900 underline">Have a code? Activate here.</a></p>
    </footer>

</body>
</html>
