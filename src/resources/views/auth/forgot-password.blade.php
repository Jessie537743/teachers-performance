<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | {{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.95); }
            to   { opacity: 1; transform: scale(1); }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 flex items-center justify-center p-4">
    <div class="w-full max-w-md" style="animation: scaleIn 0.5s ease-out both;">
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8 md:p-10">

            {{-- Logo --}}
            <div class="flex justify-center mb-6" style="animation: fadeInUp 0.5s ease-out 0.1s both;">
                <img src="{{ $appLogo }}" alt="Logo" class="w-16 h-16 object-contain rounded-full bg-blue-50 p-2 ring-2 ring-blue-100">
            </div>

            {{-- Header --}}
            <div class="text-center mb-6" style="animation: fadeInUp 0.5s ease-out 0.2s both;">
                <h1 class="text-2xl font-bold text-gray-800 mb-1.5">Forgot Password</h1>
                <p class="text-sm text-gray-500">Enter your email and we'll send you a reset link.</p>
            </div>

            {{-- Status message after successful send --}}
            @if (session('status'))
                <div class="mb-4 px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm" style="animation: fadeInUp 0.5s ease-out 0.3s both;">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Error message --}}
            @if ($errors->any())
                <div class="mb-4 px-4 py-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm" style="animation: fadeInUp 0.5s ease-out 0.3s both;">
                    {{ $errors->first() }}
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('password.email') }}" style="animation: fadeInUp 0.5s ease-out 0.4s both;">
                @csrf

                <div class="mb-5">
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1.5">Email</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                        placeholder="you@example.com"
                        class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50 focus:bg-white focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10 outline-none transition">
                </div>

                <button type="submit"
                    class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl text-sm transition-all duration-300 shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5">
                    Send Reset Link
                </button>
            </form>

            <div class="mt-6 text-center" style="animation: fadeInUp 0.5s ease-out 0.5s both;">
                <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium transition">← Back to Login</a>
            </div>

            <div class="mt-8 text-center text-xs text-gray-400">Secure password recovery</div>
        </div>
    </div>
</body>
</html>
