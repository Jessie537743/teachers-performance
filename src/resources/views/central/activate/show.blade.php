<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate school — {{ config('app.name', 'Teachers Performance Platform') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen py-10 px-4 text-slate-800">
    <div class="max-w-md mx-auto">
        <a href="/" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            Back to home
        </a>

        <div class="mt-4 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8">
            <div class="flex items-center gap-2 mb-6">
                <img src="{{ asset(config('app.default_logo')) }}" alt="" class="w-8 h-8">
                <span class="font-bold text-slate-900 tracking-tight">{{ config('app.name', 'Teachers Performance') }}</span>
            </div>

            <h1 class="text-2xl font-bold text-slate-900 mb-1">Activate your school</h1>
            @if ($tenant)
                <p class="text-sm text-slate-600 mb-6">
                    <strong class="text-slate-900">{{ $tenant->name }}</strong> is ready. Set a password for
                    <code class="font-mono text-slate-900">{{ $code->intended_admin_email }}</code> to finish onboarding.
                </p>
            @else
                <p class="text-sm text-slate-600 mb-6">Enter your activation code to set a password and sign in.</p>
            @endif

            @if ($errors->any() && ! $errors->has('code') && ! $errors->has('password'))
                <div class="mb-4 rounded-lg bg-rose-50 border border-rose-200 p-3 text-sm text-rose-700">
                    <p class="font-medium mb-1">Please fix the errors below:</p>
                    <ul class="list-disc ml-5">
                        @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('central.activate.submit') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="code" class="block text-sm font-medium text-slate-700 mb-1.5">Activation code</label>
                    <input id="code" name="code" type="text" required
                        value="{{ old('code', $code?->code) }}"
                        pattern="[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}"
                        placeholder="XXXX-YYYY-ZZZZ"
                        autofocus
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30 font-mono uppercase tracking-wider">
                    @error('code') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                @if ($code)
                    <div class="rounded-lg bg-blue-50/60 border border-blue-200 px-3 py-2.5 text-xs text-slate-700">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <div>
                                Activating as <code class="font-mono font-semibold text-slate-900">{{ $code->intended_admin_email }}</code>
                                <span class="text-slate-500">({{ $code->intended_admin_name }})</span>
                            </div>
                        </div>
                    </div>
                @endif

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1.5">Choose a password</label>
                    <input id="password" name="password" type="password" required minlength="8"
                        placeholder="••••••••"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                    <p class="mt-1 text-xs text-slate-500">At least 8 characters.</p>
                    @error('password') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1.5">Confirm password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required
                        placeholder="••••••••"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                </div>

                <button type="submit"
                    class="w-full inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-blue-950 text-white py-3 text-sm font-semibold shadow-lg shadow-blue-900/20 transition">
                    Activate school
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-500 mt-6">
            Need help? <a href="mailto:support@platform.test" class="text-blue-600 hover:text-blue-700 font-medium">Contact support</a>
        </p>
    </div>
</body>
</html>
