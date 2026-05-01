<footer class="bg-slate-900 text-slate-400 mt-auto">
    <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Teachers Performance Platform') }}. All rights reserved.</p>
        <nav class="flex items-center gap-5">
            <a href="{{ route('central.about') }}" class="hover:text-white transition-colors">About us</a>
            <a href="{{ route('central.contact') }}" class="hover:text-white transition-colors">Contact us</a>
        </nav>
    </div>
</footer>
