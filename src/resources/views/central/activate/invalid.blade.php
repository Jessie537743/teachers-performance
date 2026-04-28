<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Code not valid — Teachers Performance Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md bg-white shadow rounded-lg p-8 text-center">
        <div class="w-12 h-12 rounded-full bg-red-100 flex items-center justify-center text-red-700 text-2xl mx-auto mb-4">!</div>
        <h1 class="text-xl font-semibold text-slate-900 mb-2">This code can't be used</h1>
        <p class="text-sm text-slate-600 mb-6">{{ $reason }}</p>
        <a href="{{ route('central.activate.show') }}" class="inline-flex items-center rounded-md border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50">
            Try a different code
        </a>
    </div>
</body>
</html>
