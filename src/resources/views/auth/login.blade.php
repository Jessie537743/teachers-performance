<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['"Segoe UI"', 'Tahoma', 'Geneva', 'Verdana', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        /* Entrance animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeInRight {
            from { opacity: 0; transform: translateX(40px); }
            to   { opacity: 1; transform: translateX(0); }
        }
        @keyframes scaleIn {
            from { opacity: 0; transform: scale(0.7); }
            to   { opacity: 1; transform: scale(1); }
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%      { transform: translateY(-12px); }
        }
        @keyframes floatSlow {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50%      { transform: translateY(-18px) rotate(8deg); }
        }
        @keyframes pulse-ring {
            0%   { transform: scale(1); opacity: 0.4; }
            100% { transform: scale(1.5); opacity: 0; }
        }

        /* Auto-scroll slider (replaces marquee) */
        @keyframes slideScroll {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .slider-track {
            display: flex;
            width: max-content;
            animation: slideScroll 20s linear infinite;
        }
        .slider-track:hover { animation-play-state: paused; }

        /* Fullscreen loading overlay */
        @keyframes pulseScale {
            0%, 100% { transform: scale(1); opacity: 1; }
            50%      { transform: scale(1.08); opacity: 0.85; }
        }
        @keyframes spinSmooth {
            to { transform: rotate(360deg); }
        }
        @keyframes dotPulse {
            0%, 80%, 100% { opacity: 0.3; transform: scale(0.8); }
            40% { opacity: 1; transform: scale(1); }
        }
        .loading-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 9999;
            background: linear-gradient(135deg, #1e3a5f 0%, #1e40af 50%, #1e3a8a 100%);
            flex-direction: column; align-items: center; justify-content: center;
        }
        .loading-overlay.active { display: flex; }
        .loading-logo  { animation: pulseScale 1.8s ease-in-out infinite; }
        .loading-spinner {
            width: 40px; height: 40px;
            border: 3px solid rgba(255,255,255,0.2);
            border-top-color: white;
            border-radius: 50%;
            animation: spinSmooth 0.8s linear infinite;
        }
        .loading-dot { animation: dotPulse 1.4s ease-in-out infinite; }
        .loading-dot:nth-child(2) { animation-delay: 0.2s; }
        .loading-dot:nth-child(3) { animation-delay: 0.4s; }

        /* Floating shapes */
        .shape {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.06);
            pointer-events: none;
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                transition-duration: 0.01ms !important;
            }
            .slider-track { animation: none !important; }
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-0 m-0 font-sans overflow-hidden">

{{-- Fullscreen post-login loading overlay --}}
<div class="loading-overlay" id="loginOverlay">
    <div class="flex flex-col items-center gap-6">
        @php $customLogo = \App\Models\Setting::get('app_logo'); @endphp
        <div class="loading-logo">
            <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset('images/smcc_logo.png') }}" alt="Logo" class="w-24 h-24 object-contain rounded-full bg-white/10 p-2 ring-2 ring-white/20 shadow-2xl">
        </div>
        <div class="loading-spinner"></div>
        <div class="flex items-center gap-1.5 text-white/90 text-sm font-medium tracking-wide">
            <span>Signing you in</span>
            <span class="flex gap-0.5">
                <span class="loading-dot w-1 h-1 bg-white rounded-full inline-block"></span>
                <span class="loading-dot w-1 h-1 bg-white rounded-full inline-block"></span>
                <span class="loading-dot w-1 h-1 bg-white rounded-full inline-block"></span>
            </span>
        </div>
    </div>
</div>

<div class="flex w-full min-h-screen">
    {{-- Left panel — branding --}}
    <div class="hidden md:flex md:w-1/2 relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-900 items-center justify-center flex-col text-white p-10">

        {{-- Floating decorative shapes --}}
        <div class="shape w-64 h-64 -top-20 -left-20" style="animation: floatSlow 7s ease-in-out infinite;"></div>
        <div class="shape w-40 h-40 top-1/4 right-10" style="animation: float 5s ease-in-out infinite 1s;"></div>
        <div class="shape w-32 h-32 bottom-20 left-16" style="animation: floatSlow 6s ease-in-out infinite 0.5s;"></div>
        <div class="shape w-20 h-20 bottom-1/3 right-1/4" style="animation: float 4s ease-in-out infinite 2s;"></div>
        <div class="shape w-48 h-48 -bottom-16 -right-16" style="animation: floatSlow 8s ease-in-out infinite 1.5s;"></div>

        <div class="relative z-10 flex flex-col items-center justify-center text-center max-w-md">
            {{-- Logo with entrance animation --}}
            <div class="mb-8" style="animation: scaleIn 0.7s ease-out both;">
                <div class="relative inline-block">
                    <div class="absolute inset-0 rounded-full bg-white/10" style="animation: pulse-ring 2.5s ease-out infinite;"></div>
                    <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset('images/smcc_logo.png') }}" alt="Logo"
                         class="relative w-44 h-44 md:w-52 md:h-52 object-contain rounded-full bg-white/10 p-3 shadow-2xl ring-2 ring-white/20">
                </div>
            </div>

            {{-- Auto-scroll slider (replaces marquee) --}}
            <div class="w-full overflow-hidden" style="animation: fadeInUp 0.6s ease-out 0.3s both;">
                <div class="slider-track">
                    @php
                        $appName = \App\Models\Setting::get('app_name', 'Evaluation System');
                        $phrases = [
                            $appName,
                            'Faculty Performance Evaluation',
                            'Data-Driven Insights',
                            'Continuous Improvement',
                            $appName,
                            'Faculty Performance Evaluation',
                            'Data-Driven Insights',
                            'Continuous Improvement',
                        ];
                    @endphp
                    @foreach($phrases as $phrase)
                        <span class="text-xl md:text-2xl font-bold tracking-wide whitespace-nowrap px-8 text-white/90">{{ $phrase }}</span>
                        <span class="text-white/30 text-2xl px-2">&bull;</span>
                    @endforeach
                </div>
            </div>

            {{-- Subtitle --}}
            <p class="text-blue-100/70 text-sm mt-6 max-w-xs" style="animation: fadeInUp 0.6s ease-out 0.5s both;">
                Empowering academic excellence through transparent, data-driven faculty evaluation.
            </p>
        </div>
    </div>

    {{-- Right panel — login form --}}
    <div class="w-full md:w-1/2 flex items-center justify-center bg-gray-50 p-6">
        <div class="w-full max-w-md" style="animation: fadeInRight 0.6s ease-out 0.2s both;">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-10">

                {{-- Mobile logo (hidden on desktop) --}}
                <div class="flex justify-center mb-6 md:hidden" style="animation: scaleIn 0.5s ease-out both;">
                    <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset('images/smcc_logo.png') }}" alt="Logo" class="w-20 h-20 object-contain rounded-full bg-blue-50 p-2 ring-2 ring-blue-100">
                </div>

                <div class="mb-8 text-center" style="animation: fadeInUp 0.5s ease-out 0.3s both;">
                    <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome</h2>
                    <p class="text-sm text-gray-500">Personnel sign in with email. Students sign in with Student ID.</p>
                </div>

                @if(session('error'))
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm" style="animation: fadeInUp 0.3s ease-out both;">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm" style="animation: fadeInUp 0.3s ease-out both;">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf
                    <div class="mb-5" style="animation: fadeInUp 0.5s ease-out 0.4s both;">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="login">Email or Student ID</label>
                        <input
                            type="text"
                            name="login"
                            id="login"
                            class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200"
                            placeholder="Enter your email or student ID"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                    <div class="mb-6" style="animation: fadeInUp 0.5s ease-out 0.5s both;">
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="password">Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200 pr-12"
                                placeholder="Enter your password"
                                required
                                autocomplete="current-password"
                            >
                            <button type="button" onclick="togglePassword()" id="toggleBtn"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-none cursor-pointer w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-all">
                                <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg id="eyeOffIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="hidden"><path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                            </button>
                        </div>
                    </div>
                    <div style="animation: fadeInUp 0.5s ease-out 0.6s both;">
                        <button type="submit" id="loginBtn"
                                class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl text-sm transition-all duration-300 cursor-pointer shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none">
                            Sign In
                        </button>
                    </div>
                </form>

                <div class="mt-4 text-center" style="animation: fadeInUp 0.5s ease-out 0.65s both;">
                    <a href="{{ route('forgot-password.form') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium transition">Forgot Password?</a>
                </div>

                <div class="mt-8 text-center text-xs text-gray-400" style="animation: fadeInUp 0.5s ease-out 0.7s both;">Secure access to the evaluation system</div>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    var p = document.getElementById('password');
    var on = document.getElementById('eyeIcon');
    var off = document.getElementById('eyeOffIcon');
    if (p.type === 'password') {
        p.type = 'text';
        on.classList.add('hidden');
        off.classList.remove('hidden');
    } else {
        p.type = 'password';
        on.classList.remove('hidden');
        off.classList.add('hidden');
    }
}

document.getElementById('loginForm').addEventListener('submit', function() {
    var btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="inline-flex items-center gap-2"><span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full inline-block animate-spin"></span> Signing in...</span>';

    // Show fullscreen loading overlay with slight delay for visual transition
    setTimeout(function() {
        document.getElementById('loginOverlay').classList.add('active');
    }, 400);
});
</script>
</body>
</html>
