<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | {{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8/dist/turbo.es2017.esm.js" type="module"></script>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-0 m-0 font-sans">
<div class="flex w-full min-h-screen">
    <div class="hidden md:flex md:w-1/2 bg-gradient-to-br from-blue-600 to-blue-800 items-center justify-center flex-col text-white p-10">
        <div class="flex flex-col items-center justify-center text-center">
            <div class="mb-8">
                @php $customLogo = \App\Models\Setting::get('app_logo'); @endphp
                <img src="{{ $customLogo ? asset('storage/' . $customLogo) : asset('images/smcc_logo.png') }}" alt="Logo" class="w-44 h-44 md:w-52 md:h-52 object-contain rounded-full bg-white/10 p-3 shadow-lg ring-2 ring-white/20">
            </div>
            <marquee behavior="scroll" direction="left" scrollamount="3">
                <div class="text-2xl font-bold tracking-wide">{{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</div>
            </marquee>
        </div>
    </div>
    <div class="w-full md:w-1/2 flex items-center justify-center bg-gray-50 p-6">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-lg p-10">
            <div class="mb-8 text-center">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Welcome</h2>
                <p class="text-sm text-gray-500">Personnel sign in with email. Students sign in with Student ID.</p>
            </div>

            @if(session('error'))
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5" for="login">Email or Student ID</label>
                    <div class="relative">
                        <input
                            type="text"
                            name="login"
                            id="login"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition"
                            placeholder="Enter your email or student ID"
                            value="{{ old('login') }}"
                            required
                            autofocus
                            autocomplete="username"
                        >
                    </div>
                </div>
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 mb-1.5" for="password">Password</label>
                    <div class="relative">
                        <input
                            type="password"
                            name="password"
                            id="password"
                            class="w-full px-4 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-blue-600 transition pr-12"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 bg-transparent border-none cursor-pointer text-lg text-gray-500 hover:text-gray-700" onclick="togglePassword()" id="toggleBtn">&#128065;</button>
                    </div>
                </div>
                <button type="submit" class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-sm transition-colors duration-200 cursor-pointer disabled:opacity-60 disabled:cursor-not-allowed" id="loginBtn">Sign In</button>
            </form>
            <div class="mt-8 text-center text-xs text-gray-400">Secure access to the evaluation system</div>
        </div>
    </div>
</div>
<script>
function togglePassword() {
    const p = document.getElementById("password");
    const b = document.getElementById("toggleBtn");
    if (p.type === "password") {
        p.type = "text";
        b.textContent = "\u{1F648}";
    } else {
        p.type = "password";
        b.textContent = "\u{1F441}";
    }
}
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    btn.classList.add('opacity-60');
    btn.innerHTML = '<span class="inline-flex items-center gap-2"><span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full inline-block animate-spin"></span> Signing in...</span>';
});
</script>
</body>
</html>
