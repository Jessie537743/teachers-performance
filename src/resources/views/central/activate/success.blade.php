<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $tenant->name }} — activated</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white shadow rounded-lg p-8 text-center">
        <div class="w-12 h-12 rounded-full bg-green-100 flex items-center justify-center text-green-700 text-2xl mx-auto mb-4">✓</div>
        <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ $tenant->name }} is ready</h1>
        <p class="text-sm text-slate-600 mb-6">
            Sign in as <code class="font-mono">{{ $adminEmail }}</code> with the password you just set.
        </p>
        <a href="{{ $loginUrl }}" class="inline-flex items-center rounded-md bg-slate-900 px-6 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Open {{ $tenant->subdomain }}.localhost
        </a>
    </div>
</body>
</html>
