<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration submitted</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
<div class="max-w-md w-full bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8 text-center">
    <div class="mx-auto w-14 h-14 rounded-full bg-emerald-100 grid place-items-center text-emerald-700 mb-4">
        <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-xl font-bold text-slate-900 mb-2">Registration submitted</h1>
    <p class="text-sm text-slate-600 mb-4">
        Thanks — we received your application
        @if ($email) and sent a confirmation to <code class="font-mono text-slate-800">{{ $email }}</code>@endif.
        You'll get another email once your registration is reviewed.
    </p>
    <a href="{{ route('login') }}" class="inline-flex items-center rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-5 py-2.5">
        Back to sign in
    </a>
</div>
</body>
</html>
