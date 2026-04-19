<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Super Admin' }} — Platform Console</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full flex flex-col">
    @auth('super_admin')
    <header class="bg-slate-900 text-white">
        <div class="max-w-6xl mx-auto px-6 py-3 flex items-center justify-between">
            <a href="{{ route('admin.tenants.index') }}" class="font-semibold tracking-tight">Platform Console</a>
            <nav class="flex items-center gap-6 text-sm">
                <a href="{{ route('admin.tenants.index') }}" class="hover:text-slate-300">Schools</a>
                <span class="text-slate-400">{{ auth('super_admin')->user()->email }}</span>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="text-slate-300 hover:text-white">Sign out</button>
                </form>
            </nav>
        </div>
    </header>
    @endauth

    <main class="flex-1">
        <div class="max-w-6xl mx-auto px-6 py-8">
            @if (session('status'))
                <div class="mb-6 rounded-md bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('status') }}
                </div>
            @endif
            @if (session('error'))
                <div class="mb-6 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            {{ $slot ?? '' }}
            @yield('content')
        </div>
    </main>
</body>
</html>
