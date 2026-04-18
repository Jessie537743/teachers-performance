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

@php
    $appName = \App\Models\Setting::get('app_name', 'Evaluation System');
@endphp
<div class="flex w-full min-h-screen bg-white">

    {{-- Left panel — form --}}
    <div class="w-full md:w-[45%] flex flex-col bg-white px-6 md:px-14 py-8 relative z-10">

        {{-- Brand row --}}
        <div class="flex items-center gap-2" style="animation: fadeInUp 0.4s ease-out both;">
            <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset('images/smcc_logo.png') }}" alt="Logo" class="w-9 h-9 object-contain">
            <span class="text-lg font-semibold text-slate-800 truncate">{{ $appName }}</span>
        </div>

        {{-- Form block centered vertically --}}
        <div class="flex-1 flex flex-col justify-center">
            <div class="w-full max-w-sm mx-auto" style="animation: fadeInUp 0.5s ease-out 0.1s both;">

                {{-- Welcome --}}
                <p class="text-sm text-slate-500 mb-1">Welcome to</p>
                <h1 class="text-3xl md:text-4xl font-bold text-slate-900 mb-8 leading-tight">{{ $appName }}</h1>

                {{-- Announcements (login-visible) --}}
                @include('layouts.partials.login-announcements')

                @if(session('error'))
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide" for="login">Username</label>
                        <input
                            type="text"
                            name="login"
                            id="login"
                            class="w-full px-4 py-3 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                            placeholder="Email or Student ID"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5 uppercase tracking-wide" for="password">Password</label>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                class="w-full px-4 py-3 pr-12 border border-slate-200 rounded-lg text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
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

                    <div class="flex items-center justify-between text-sm">
                        <label class="inline-flex items-center gap-2 text-slate-500 cursor-pointer">
                            <input type="checkbox" name="remember" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                            <span>Remember me</span>
                        </label>
                        <a href="{{ route('forgot-password.form') }}" class="text-blue-600 hover:text-blue-800 font-medium transition">Forgot Password?</a>
                    </div>

                    <button type="submit" id="loginBtn"
                            class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-lg text-sm tracking-wide uppercase transition-all duration-300 cursor-pointer shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:translate-y-0 disabled:hover:shadow-none">
                        Login
                    </button>
                </form>

                <p class="mt-5 text-center text-sm text-slate-500">
                    Need access? <a href="{{ route('forgot-password.form') }}" class="text-blue-600 hover:text-blue-800 font-semibold">Request an account</a>
                </p>
            </div>
        </div>

        {{-- Footer --}}
        <div class="text-center text-xs text-slate-400 mt-6">
            <a href="{{ route('help.index') }}" class="hover:text-slate-600">FAQ</a>
            <span class="mx-2">|</span>
            <span>{{ $appName }}</span>
            <span class="mx-2">|</span>
            <a href="mailto:support@localhost" class="hover:text-slate-600">Support</a>
        </div>
    </div>

    {{-- Right panel — info with curved left edge --}}
    <div class="hidden md:block relative md:w-[55%]">
        {{-- Curved blue background --}}
        <div class="absolute inset-0 bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-900 overflow-hidden"
             style="clip-path: ellipse(118% 140% at 100% 50%);">

            {{-- Giant watermark text (covers most of the panel vertically) --}}
            <div class="absolute right-[-1rem] top-1/2 -translate-y-1/2 text-white/[0.06] font-black leading-none select-none pointer-events-none whitespace-nowrap tracking-tighter"
                 style="font-size: clamp(12rem, 22vw, 24rem);" aria-hidden="true">
                LOGIN
            </div>

            {{-- Subtle floating orbs --}}
            <div class="shape w-72 h-72 -top-16 right-28" style="animation: floatSlow 7s ease-in-out infinite;"></div>
            <div class="shape w-40 h-40 top-1/3 right-16" style="animation: float 5s ease-in-out infinite 1s;"></div>
            <div class="shape w-24 h-24 bottom-1/4 right-1/3" style="animation: float 4s ease-in-out infinite 2s;"></div>
            <div class="shape w-56 h-56 -bottom-16 -right-10" style="animation: floatSlow 8s ease-in-out infinite 1.5s;"></div>
        </div>

        {{-- Content --}}
        <div class="relative z-10 h-full flex items-center px-10 lg:px-16 py-16 text-white" style="animation: fadeInRight 0.6s ease-out 0.2s both;">
            <div class="max-w-lg">

                <h2 class="text-2xl font-bold mb-3">About {{ $appName }}</h2>
                <p class="text-blue-100/90 text-sm leading-relaxed mb-8">
                    A data-driven faculty performance evaluation platform. Students, peers, deans, and heads submit evaluations; the system aggregates and surfaces actionable insights to help the institution support continuous teaching improvement.
                </p>

                <h2 class="text-2xl font-bold mb-3">Features</h2>
                <ul class="space-y-3 text-sm text-blue-100/90">
                    <li class="flex gap-2">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-white/70 flex-shrink-0"></span>
                        <span>Structured student, peer, self, and dean evaluations with tailored criteria per role.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-white/70 flex-shrink-0"></span>
                        <span>Period-scoped analytics and sentiment analysis of qualitative comments.</span>
                    </li>
                    <li class="flex gap-2">
                        <span class="mt-1.5 w-1.5 h-1.5 rounded-full bg-white/70 flex-shrink-0"></span>
                        <span>Department and institution-wide reports with intervention recommendations.</span>
                    </li>
                </ul>
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
