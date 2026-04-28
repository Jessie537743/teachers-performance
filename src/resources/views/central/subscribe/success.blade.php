<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>School provisioned — check your email</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-lg w-full bg-white rounded-lg shadow p-8 text-center">
        <div class="mx-auto w-12 h-12 rounded-full bg-green-100 flex items-center justify-center mb-4">
            <svg class="w-6 h-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
            </svg>
        </div>

        <h1 class="text-2xl font-semibold text-slate-900 mb-2">Payment successful</h1>
        <p class="text-slate-600 mb-6">
            <strong class="text-slate-900">{{ $tenant->name }}</strong> is provisioned. We've emailed your activation code to
            <code class="font-mono text-slate-800">{{ $maskedEmail }}</code>.
        </p>

        <div class="bg-slate-50 border border-slate-200 rounded-md p-4 mb-6 text-left">
            <p class="text-xs uppercase tracking-wide text-slate-500 mb-1">Your school URL</p>
            <p class="font-mono text-sm text-slate-900 break-all">{{ $tenantUrl }}</p>
        </div>

        <p class="text-sm text-slate-600 mb-6">
            Open the email, click the activation link, and set your admin password to sign in.
        </p>

        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ route('central.activate.show') }}" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-5 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Enter activation code
            </a>
            <a href="/" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-5 py-2 text-sm text-slate-700 hover:bg-slate-50">
                Back to home
            </a>
        </div>

        @if (config('mail.default') === 'log')
            <p class="mt-6 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-md p-3 text-left">
                <strong>Dev note:</strong> Mail driver is <code>log</code> — the activation email was written to <code>storage/logs/laravel.log</code>, not actually sent.
            </p>
        @endif
    </div>
</body>
</html>
