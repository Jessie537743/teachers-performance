<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | {{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>
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
        .shape {
            position: absolute; border-radius: 50%;
            background: rgba(255,255,255,0.06);
            pointer-events: none;
        }
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

<div class="flex w-full min-h-screen">
    {{-- Left panel -- branding --}}
    <div class="hidden md:flex md:w-1/2 relative overflow-hidden bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-900 items-center justify-center flex-col text-white p-10">
        <div class="shape w-64 h-64 -top-20 -left-20" style="animation: floatSlow 7s ease-in-out infinite;"></div>
        <div class="shape w-40 h-40 top-1/4 right-10" style="animation: float 5s ease-in-out infinite 1s;"></div>
        <div class="shape w-32 h-32 bottom-20 left-16" style="animation: floatSlow 6s ease-in-out infinite 0.5s;"></div>
        <div class="shape w-20 h-20 bottom-1/3 right-1/4" style="animation: float 4s ease-in-out infinite 2s;"></div>
        <div class="shape w-48 h-48 -bottom-16 -right-16" style="animation: floatSlow 8s ease-in-out infinite 1.5s;"></div>

        <div class="relative z-10 flex flex-col items-center justify-center text-center max-w-md">
            @php $customLogo = \App\Models\Setting::get('app_logo'); @endphp
            <div class="mb-8" style="animation: scaleIn 0.7s ease-out both;">
                <div class="relative inline-block">
                    <div class="absolute inset-0 rounded-full bg-white/10" style="animation: pulse-ring 2.5s ease-out infinite;"></div>
                    <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset(config('app.default_logo')) }}" alt="Logo"
                         class="relative w-44 h-44 md:w-52 md:h-52 object-contain rounded-full bg-white/10 p-3 shadow-2xl ring-2 ring-white/20">
                </div>
            </div>

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

            <p class="text-blue-100/70 text-sm mt-6 max-w-xs" style="animation: fadeInUp 0.6s ease-out 0.5s both;">
                Empowering academic excellence through transparent, data-driven faculty evaluation.
            </p>
        </div>
    </div>

    {{-- Right panel --}}
    <div class="w-full md:w-1/2 flex items-center justify-center bg-gray-50 p-6">
        <div class="w-full max-w-md" style="animation: fadeInRight 0.6s ease-out 0.2s both;">
            <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-10">

                {{-- Mobile logo --}}
                <div class="flex justify-center mb-6 md:hidden" style="animation: scaleIn 0.5s ease-out both;">
                    @php $customLogo = $customLogo ?? \App\Models\Setting::get('app_logo'); @endphp
                    <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset(config('app.default_logo')) }}" alt="Logo" class="w-20 h-20 object-contain rounded-full bg-blue-50 p-2 ring-2 ring-blue-100">
                </div>

                @if(!empty($submitted))
                    {{-- State 3: Success --}}
                    <div class="text-center" style="animation: fadeInUp 0.5s ease-out 0.3s both;">
                        <div class="mx-auto mb-4 w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </div>
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Password Reset Request Submitted</h2>
                        <p class="text-sm text-gray-500 mt-3 leading-relaxed">Your request has been submitted and is awaiting administrator approval. You will be able to login once your request is approved.</p>
                        <div class="mt-8">
                            <a href="{{ route('login') }}" class="inline-block w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl text-sm transition-all duration-300 text-center shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40">Back to Login</a>
                        </div>
                    </div>

                @elseif(!empty($verified))
                    {{-- State 2: Password form --}}
                    <div class="mb-8 text-center" style="animation: fadeInUp 0.5s ease-out 0.3s both;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Set New Password</h2>
                        <p class="text-sm text-gray-500">Identity verified for: <strong>{{ $verified_email }}</strong></p>
                    </div>

                    @if(session('error'))
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('forgot-password.submit') }}">
                        @csrf
                        <input type="hidden" name="user_id" value="{{ $verified_user_id }}">

                        <div class="mb-5" style="animation: fadeInUp 0.5s ease-out 0.4s both;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="password">New Password</label>
                            <input type="password" name="password" id="password" required minlength="8"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200"
                                   placeholder="Minimum 8 characters">
                        </div>

                        <div class="mb-6" style="animation: fadeInUp 0.5s ease-out 0.5s both;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="password_confirmation">Confirm Password</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" required minlength="8"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200"
                                   placeholder="Re-enter your new password">
                        </div>

                        <div style="animation: fadeInUp 0.5s ease-out 0.6s both;">
                            <button type="submit"
                                    class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl text-sm transition-all duration-300 cursor-pointer shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 active:translate-y-0">
                                Submit Reset Request
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 text-center" style="animation: fadeInUp 0.5s ease-out 0.7s both;">
                        <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium transition">Back to Login</a>
                    </div>

                @else
                    {{-- State 1: Identity verification --}}
                    <div class="mb-8 text-center" style="animation: fadeInUp 0.5s ease-out 0.3s both;">
                        <h2 class="text-2xl font-bold text-gray-800 mb-2">Forgot Password</h2>
                        <p class="text-sm text-gray-500">Verify your identity to request a password reset.</p>
                    </div>

                    @if(session('error'))
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm" style="animation: fadeInUp 0.3s ease-out both;">{{ session('error') }}</div>
                    @endif
                    @if($errors->any())
                        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm" style="animation: fadeInUp 0.3s ease-out both;">{{ $errors->first() }}</div>
                    @endif

                    <form method="POST" action="{{ route('forgot-password.verify') }}">
                        @csrf
                        <div class="mb-5" style="animation: fadeInUp 0.5s ease-out 0.4s both;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="email">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200"
                                   placeholder="Enter your email address">
                        </div>

                        <div class="mb-5" style="animation: fadeInUp 0.5s ease-out 0.45s both;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="date_of_birth">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="date_of_birth" value="{{ old('date_of_birth') }}" required
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200">
                        </div>

                        <div class="mb-4 p-3 bg-blue-50 border border-blue-200 text-blue-700 rounded-xl text-xs" style="animation: fadeInUp 0.5s ease-out 0.5s both;">
                            Students must also provide Course and Year Level.
                        </div>

                        <div class="mb-5" style="animation: fadeInUp 0.5s ease-out 0.55s both;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="course">Course</label>
                            <input type="text" name="course" id="course" value="{{ old('course') }}"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200"
                                   placeholder="e.g. BSIT, BSCS (students only)">
                        </div>

                        <div class="mb-6" style="animation: fadeInUp 0.5s ease-out 0.6s both;">
                            <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="year_level">Year Level</label>
                            <input type="text" name="year_level" id="year_level" value="{{ old('year_level') }}"
                                   class="w-full px-4 py-3 border border-gray-200 rounded-xl text-sm bg-gray-50/50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all duration-200"
                                   placeholder="e.g. 1, 2, 3, 4 (students only)">
                        </div>

                        <div style="animation: fadeInUp 0.5s ease-out 0.65s both;">
                            <button type="submit"
                                    class="w-full py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold rounded-xl text-sm transition-all duration-300 cursor-pointer shadow-lg shadow-blue-500/25 hover:shadow-blue-500/40 hover:-translate-y-0.5 active:translate-y-0">
                                Verify Identity
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 text-center" style="animation: fadeInUp 0.5s ease-out 0.7s both;">
                        <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium transition">Back to Login</a>
                    </div>
                @endif

                <div class="mt-8 text-center text-xs text-gray-400" style="animation: fadeInUp 0.5s ease-out 0.8s both;">Secure password recovery</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
