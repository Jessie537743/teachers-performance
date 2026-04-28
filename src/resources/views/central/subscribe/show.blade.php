<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Subscribe — {{ $plan['name'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen py-10 px-4">
    <div class="max-w-5xl mx-auto">
        <a href="/" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to plans</a>

        <div class="mt-4 grid md:grid-cols-3 gap-6">
            {{-- Form --}}
            <div class="md:col-span-2 bg-white rounded-lg shadow p-8">
                <h1 class="text-2xl font-semibold text-slate-900 mb-1">Set up your school</h1>
                <p class="text-sm text-slate-600 mb-6">We'll provision your tenant and email your activation code.</p>

                @if ($errors->any())
                    <div class="mb-4 rounded-md bg-red-50 border border-red-200 p-3 text-sm text-red-700">
                        <p class="font-medium mb-1">Please fix the errors below:</p>
                        <ul class="list-disc ml-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('central.subscribe.process') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="plan" value="{{ $planSlug }}">

                    <fieldset class="space-y-4">
                        <legend class="text-sm font-semibold text-slate-900 mb-2">School details</legend>

                        <div>
                            <label for="name" class="block text-sm font-medium text-slate-700 mb-1">School name</label>
                            <input id="name" name="name" type="text" required value="{{ old('name') }}"
                                placeholder="St. Mary's Academy"
                                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>

                        <div>
                            <label for="subdomain" class="block text-sm font-medium text-slate-700 mb-1">Subdomain</label>
                            <div class="flex">
                                <input id="subdomain" name="subdomain" type="text" required value="{{ old('subdomain') }}"
                                    pattern="[a-z0-9](?:[a-z0-9-]{0,30}[a-z0-9])?"
                                    placeholder="stmarys"
                                    class="flex-1 rounded-l-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 font-mono lowercase">
                                <span class="inline-flex items-center px-3 rounded-r-md border border-l-0 border-slate-300 bg-slate-50 text-slate-500 text-sm font-mono">
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
                                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>

                        <div>
                            <label for="admin_email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                            <input id="admin_email" name="admin_email" type="email" required value="{{ old('admin_email') }}"
                                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
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
                                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                        </div>

                        <div>
                            <label for="card_number" class="block text-sm font-medium text-slate-700 mb-1">Card number</label>
                            <input id="card_number" name="card_number" type="text" required
                                value="{{ old('card_number', '4242 4242 4242 4242') }}"
                                inputmode="numeric" autocomplete="cc-number"
                                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 font-mono">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="card_expiry" class="block text-sm font-medium text-slate-700 mb-1">Expiry (MM/YY)</label>
                                <input id="card_expiry" name="card_expiry" type="text" required
                                    value="{{ old('card_expiry', '12/30') }}"
                                    placeholder="MM/YY" pattern="(0[1-9]|1[0-2])\/\d{2}"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 font-mono">
                            </div>
                            <div>
                                <label for="card_cvc" class="block text-sm font-medium text-slate-700 mb-1">CVC</label>
                                <input id="card_cvc" name="card_cvc" type="text" required
                                    value="{{ old('card_cvc', '123') }}"
                                    inputmode="numeric" pattern="\d{3,4}" maxlength="4"
                                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 font-mono">
                            </div>
                        </div>
                    </fieldset>

                    <button type="submit" class="w-full rounded-md bg-slate-900 text-white py-3 text-sm font-medium hover:bg-slate-800">
                        @if ((float) $plan['price'] === 0.0)
                            Start free — provision school
                        @else
                            Pay ${{ $plan['price'] }} {{ $plan['period'] }} & provision school
                        @endif
                    </button>

                    <p class="text-xs text-slate-500 text-center">
                        By subscribing you agree to the platform terms. Activation code valid for 30 days.
                    </p>
                </form>
            </div>

            {{-- Order summary --}}
            <aside class="bg-white rounded-lg shadow p-6 h-fit">
                <h2 class="text-sm font-semibold text-slate-900 mb-4">Order summary</h2>

                <div class="flex items-baseline justify-between mb-1">
                    <span class="text-base font-medium text-slate-900">{{ $plan['name'] }} plan</span>
                    <span class="text-base font-semibold text-slate-900">
                        @if (is_numeric($plan['price']))
                            ${{ $plan['price'] }}
                        @else
                            {{ $plan['price'] }}
                        @endif
                    </span>
                </div>
                <p class="text-xs text-slate-500 mb-4">{{ $plan['period'] }}</p>

                <ul class="space-y-2 text-sm text-slate-700 mb-6">
                    @foreach ($plan['features'] as $feature)
                        <li class="flex items-start gap-2">
                            <span class="text-green-600 flex-shrink-0">&check;</span>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>

                <div class="pt-4 border-t border-slate-200 flex items-baseline justify-between">
                    <span class="text-sm text-slate-600">Total today</span>
                    <span class="text-xl font-bold text-slate-900">
                        @if (is_numeric($plan['price']))
                            ${{ $plan['price'] }}
                        @else
                            {{ $plan['price'] }}
                        @endif
                    </span>
                </div>
            </aside>
        </div>
    </div>
</body>
</html>
