<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscribe — {{ $plan['name'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen py-10 px-4 text-slate-800">
    @php
        $monthly = (int) ($plan['prices']['monthly'] ?? 0);
        $yearly  = (int) ($plan['prices']['yearly']  ?? 0);
        $monthlyEquivalentOfYearly = $yearly > 0 ? round($yearly / 12, 2) : 0;
        $yearlySavings = $monthly > 0 ? max(0, ($monthly * 12) - $yearly) : 0;
    @endphp

    <div class="max-w-5xl mx-auto">
        <a href="/" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to plans</a>

        <div class="mt-4 grid md:grid-cols-3 gap-6">
            {{-- Form --}}
            <div class="md:col-span-2 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8">
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Set up your school</h1>
                <p class="text-sm text-slate-600 mb-6">We'll provision your tenant and email your activation code.</p>

                @if ($errors->any())
                    <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 p-3 text-sm text-rose-700">
                        <p class="font-medium mb-1">Please fix the errors below:</p>
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('central.subscribe.process') }}" class="space-y-6" id="subscribeForm">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $planSlug }}">

                    {{-- Billing cycle toggle --}}
                    <fieldset>
                        <legend class="text-sm font-semibold text-slate-900 mb-3">Billing cycle</legend>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="relative cursor-pointer">
                                <input type="radio" name="billing_cycle" value="monthly" class="peer sr-only" {{ $cycle === 'monthly' ? 'checked' : '' }} onchange="updateTotal()">
                                <div class="rounded-xl border-2 border-slate-200 p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50/50 hover:border-slate-300 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-900">Monthly</span>
                                        <span class="text-lg font-bold">${{ $monthly }}<span class="text-xs font-normal text-slate-500">/mo</span></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">Billed every month. Cancel anytime.</p>
                                </div>
                                <span class="absolute top-3 right-3 hidden peer-checked:flex w-5 h-5 rounded-full bg-blue-600 text-white items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </label>

                            <label class="relative cursor-pointer">
                                <input type="radio" name="billing_cycle" value="yearly" class="peer sr-only" {{ $cycle === 'yearly' ? 'checked' : '' }} onchange="updateTotal()">
                                <div class="rounded-xl border-2 border-slate-200 p-4 peer-checked:border-blue-600 peer-checked:bg-blue-50/50 hover:border-slate-300 transition">
                                    <div class="flex items-center justify-between">
                                        <span class="font-semibold text-slate-900 inline-flex items-center gap-2">
                                            Yearly
                                            @if ($yearlySavings > 0)
                                                <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">Save ${{ $yearlySavings }}</span>
                                            @endif
                                        </span>
                                        <span class="text-lg font-bold">${{ $yearly }}<span class="text-xs font-normal text-slate-500">/yr</span></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        Billed yearly. Equivalent to <strong>${{ number_format($monthlyEquivalentOfYearly, 2) }}/mo</strong>.
                                    </p>
                                </div>
                                <span class="absolute top-3 right-3 hidden peer-checked:flex w-5 h-5 rounded-full bg-blue-600 text-white items-center justify-center">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                            </label>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4 pt-4 border-t border-slate-200">
                        <legend class="text-sm font-semibold text-slate-900 mb-2">School details</legend>
                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">School name</label>
                            <input id="name" name="name" type="text" required value="{{ old('name') }}"
                                placeholder="St. Mary's Academy"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label for="subdomain" class="block text-sm font-medium text-slate-700 mb-1">Subdomain</label>
                            <div class="flex">
                                <input id="subdomain" name="subdomain" type="text" required value="{{ old('subdomain') }}"
                                    pattern="[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?" placeholder="stmarys"
                                    class="flex-1 rounded-l-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30 font-mono lowercase">
                                <span class="inline-flex items-center px-3 rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 text-slate-500 text-sm font-mono">
                                    .{{ parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-slate-500">2-32 chars, lowercase letters, digits, hyphens.</p>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4 pt-4 border-t border-slate-200">
                        <legend class="text-sm font-semibold text-slate-900 mb-2">First admin (you)</legend>
                        <div>
                            <label for="admin_name" class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                            <input id="admin_name" name="admin_name" type="text" required value="{{ old('admin_name') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input id="admin_email" name="admin_email" type="email" required value="{{ old('admin_email') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                            <p class="mt-1 text-xs text-slate-500">We'll send your activation code here.</p>
                        </div>
                    </fieldset>

                    <fieldset class="space-y-4 pt-4 border-t border-slate-200">
                        <legend class="text-sm font-semibold text-slate-900 mb-2">Payment details (simulated)</legend>
                        <p class="text-xs text-slate-500 -mt-1 mb-2">
                            Demo mode — no real charge. Try <code class="font-mono">4242 4242 4242 4242</code> to succeed,
                            <code class="font-mono">4000 0000 0000 0002</code> to test a decline.
                        </p>
                        <div>
                            <label for="card_name" class="block text-sm font-medium text-slate-700 mb-1">Name on card</label>
                            <input id="card_name" name="card_name" type="text" required value="{{ old('card_name') }}"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label for="card_number" class="block text-sm font-medium text-slate-700 mb-1">Card number</label>
                            <input id="card_number" name="card_number" type="text" required
                                value="{{ old('card_number', '4242 4242 4242 4242') }}" inputmode="numeric" autocomplete="cc-number"
                                class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30 font-mono">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="card_expiry" class="block text-sm font-medium text-slate-700 mb-1">Expiry (MM/YY)</label>
                                <input id="card_expiry" name="card_expiry" type="text" required
                                    value="{{ old('card_expiry', '12/30') }}"
                                    placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/\d{2}"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30 font-mono">
                            </div>
                            <div>
                                <label for="card_cvc" class="block text-sm font-medium text-slate-700 mb-1">CVC</label>
                                <input id="card_cvc" name="card_cvc" type="text" required
                                    value="{{ old('card_cvc', '123') }}"
                                    inputmode="numeric" pattern="\d{3,4}" maxlength="4"
                                    class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30 font-mono">
                            </div>
                        </div>
                    </fieldset>

                    <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-blue-950 text-white py-3 text-sm font-semibold shadow-lg shadow-blue-900/20 transition">
                        @if ($monthly === 0)
                            Start free — provision school
                        @else
                            <span id="ctaLabel">Subscribe & provision school</span>
                        @endif
                    </button>

                    <p class="text-xs text-slate-500 text-center">
                        By subscribing you agree to recurring billing. Cancel anytime from your school's billing page.
                    </p>
                </form>
            </div>

            {{-- Order summary --}}
            <aside class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6 h-fit lg:sticky lg:top-6">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Order summary</h2>

                <div class="flex items-baseline justify-between mb-1">
                    <span class="text-base font-medium text-slate-900">{{ $plan['name'] }} plan</span>
                    <span id="summaryAmount" class="text-base font-semibold text-slate-900">${{ $cycle === 'yearly' ? $yearly : $monthly }}</span>
                </div>
                <p id="summaryPeriod" class="text-xs text-slate-500 mb-4">{{ $cycle === 'yearly' ? 'per year' : 'per month' }}</p>

                <ul class="space-y-2 text-sm text-slate-700 mb-6">
                    @foreach ($plan['features'] as $feature)
                        <li class="flex items-start gap-2">
                            <span class="text-emerald-600 flex-shrink-0">&check;</span>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="pt-4 border-t border-slate-200">
                    <div class="flex items-baseline justify-between">
                        <span class="text-sm text-slate-600">Total today</span>
                        <span id="summaryTotal" class="text-2xl font-bold text-slate-900">${{ $cycle === 'yearly' ? $yearly : $monthly }}</span>
                    </div>
                    <p id="summaryRenewal" class="text-xs text-slate-500 mt-2">
                        Renews {{ $cycle === 'yearly' ? 'yearly' : 'monthly' }} at the same price. You can cancel anytime.
                    </p>
                </div>
            </aside>
        </div>
    </div>

    <script>
        const monthly = {{ $monthly }};
        const yearly  = {{ $yearly }};
        function updateTotal() {
            const cycle = document.querySelector('input[name=billing_cycle]:checked')?.value;
            const amount = cycle === 'yearly' ? yearly : monthly;
            const period = cycle === 'yearly' ? 'per year' : 'per month';
            document.getElementById('summaryAmount').textContent = '$' + amount;
            document.getElementById('summaryPeriod').textContent = period;
            document.getElementById('summaryTotal').textContent = '$' + amount;
            document.getElementById('summaryRenewal').textContent = 'Renews ' + (cycle === 'yearly' ? 'yearly' : 'monthly') + ' at the same price. You can cancel anytime.';
            const cta = document.getElementById('ctaLabel');
            if (cta && amount > 0) cta.textContent = 'Pay $' + amount + ' (' + period + ') & provision school';
        }
        updateTotal();
    </script>
</body>
</html>
