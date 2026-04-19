<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School suspended — {{ $tenant->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center">
    <div class="bg-white shadow rounded-lg p-8 max-w-md">
        <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ $tenant->name }} is currently {{ $tenant->status }}.</h1>
        <p class="text-sm text-slate-600">Logins are temporarily blocked. Contact your platform administrator if this is unexpected.</p>
    </div>
</body>
</html>
