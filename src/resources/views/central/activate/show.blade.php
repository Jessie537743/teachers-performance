<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Activate school — Teachers Performance Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white shadow rounded-lg p-8">
        <h1 class="text-2xl font-semibold text-slate-900 mb-1">Activate your school</h1>
        @if ($tenant)
            <p class="text-sm text-slate-600 mb-6">
                <strong class="text-slate-900">{{ $tenant->name }}</strong> is ready. Set a password for
                <code class="font-mono">{{ $code->intended_admin_email }}</code> to finish onboarding.
            </p>
        @else
            <p class="text-sm text-slate-600 mb-6">Enter your activation code to set a password and sign in.</p>
        @endif

        <form method="POST" action="{{ route('central.activate.submit') }}" class="space-y-4">
            @csrf

            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 mb-1">Activation code</label>
                <input id="code" name="code" type="text" required
                    value="{{ old('code', $code?->code) }}"
                    pattern="[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}"
                    placeholder="XXXX-YYYY-ZZZZ"
                    autofocus
                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500 font-mono uppercase">
                @error('code') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($code)
                <div class="rounded-md bg-slate-50 border border-slate-200 px-3 py-2 text-xs text-slate-600">
                    Activating as: <code class="font-mono">{{ $code->intended_admin_email }}</code> ({{ $code->intended_admin_name }})
                </div>
            @endif

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Choose a password</label>
                <input id="password" name="password" type="password" required minlength="8"
                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
                <p class="mt-1 text-xs text-slate-500">At least 8 characters.</p>
                @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
            </div>

            <button type="submit" class="w-full rounded-md bg-slate-900 text-white py-2 hover:bg-slate-800">
                Activate school
            </button>
        </form>
    </div>
</body>
</html>
