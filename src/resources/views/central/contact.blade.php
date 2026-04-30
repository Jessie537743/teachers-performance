<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Contact Us — {{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform')) }}</title>
    <meta name="description" content="Get in touch with the Teachers Performance Platform team — sales, support, partnerships.">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 text-slate-800 antialiased">

    {{-- Top Nav --}}
    <nav class="sticky top-0 z-40 bg-white/80 backdrop-blur border-b border-slate-200/70">
        <div class="max-w-7xl mx-auto px-6 h-16 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2">
                <img src="{{ $appLogo }}" alt="" class="w-8 h-8">
                <span class="font-bold text-slate-900 tracking-tight">{{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance')) }}</span>
            </a>
            <div class="hidden md:flex items-center gap-8 text-sm">
                <a href="/#features" class="text-slate-600 hover:text-slate-900">Features</a>
                <a href="/#how" class="text-slate-600 hover:text-slate-900">How it works</a>
                <a href="/#pricing" class="text-slate-600 hover:text-slate-900">Pricing</a>
                <a href="{{ route('central.about') }}" class="text-slate-600 hover:text-slate-900">About</a>
                <a href="{{ route('central.contact') }}" class="text-slate-900 font-semibold">Contact</a>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('central.activate.show') }}" class="hidden sm:inline-flex text-sm text-slate-600 hover:text-slate-900 px-3 py-2">Activate</a>
                <a href="/#pricing" class="inline-flex items-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 shadow-sm">
                    Get started
                </a>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <header class="bg-gradient-to-br from-blue-700 via-blue-800 to-blue-900 text-white">
        <div class="max-w-5xl mx-auto px-6 py-16 md:py-20">
            <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/20 text-xs font-medium tracking-wide uppercase px-3 py-1 mb-5">
                Get in touch
            </span>
            <h1 class="text-4xl md:text-5xl font-extrabold leading-[1.05] tracking-tight mb-4">Contact us</h1>
            <p class="text-lg text-blue-100/90 max-w-2xl">
                Questions about the platform, pricing, or onboarding your school? Pick the channel that fits — we'll usually reply within one business day.
            </p>
        </div>
    </header>

    <main class="py-14">
        <div class="max-w-5xl mx-auto px-6 grid lg:grid-cols-5 gap-8">

            {{-- Contact form --}}
            <div class="lg:col-span-3 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8">
                <h2 class="text-xl font-bold text-slate-900 mb-1">Send us a message</h2>
                <p class="text-sm text-slate-500 mb-6">We'll route this to the right person on our team.</p>

                <form action="mailto:support@teach-matrix.app" method="post" enctype="text/plain" class="space-y-5">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_name" class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
                            <input id="contact_name" name="name" type="text" required maxlength="120"
                                   placeholder="Juan Dela Cruz"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label for="contact_email" class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                            <input id="contact_email" name="email" type="email" required maxlength="191"
                                   placeholder="you@yourschool.edu.ph"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label for="contact_school" class="block text-sm font-medium text-slate-700 mb-1.5">School / institution</label>
                            <input id="contact_school" name="school" type="text" maxlength="191"
                                   placeholder="St. Paul University"
                                   class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                        <div>
                            <label for="contact_topic" class="block text-sm font-medium text-slate-700 mb-1.5">Topic</label>
                            <select id="contact_topic" name="topic"
                                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                                <option value="sales">Sales / pricing</option>
                                <option value="support">Support / bug</option>
                                <option value="onboarding">Onboarding help</option>
                                <option value="partnership">Partnership</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label for="contact_message" class="block text-sm font-medium text-slate-700 mb-1.5">Message</label>
                        <textarea id="contact_message" name="message" rows="6" required maxlength="2000"
                                  placeholder="Tell us a bit about your school and what you're hoping to solve."
                                  class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30"></textarea>
                    </div>

                    <button type="submit"
                            class="w-full inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-blue-950 text-white py-3 text-sm font-semibold shadow-lg shadow-blue-900/20 transition">
                        Send message
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>

                    <p class="text-xs text-slate-500 text-center">
                        Submitting opens your email client with a pre-filled message to support@teach-matrix.app.
                    </p>
                </form>
            </div>

            {{-- Channels --}}
            <aside class="lg:col-span-2 space-y-4">
                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-600/10 text-blue-700 grid place-items-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900">Sales</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Plan questions, demos, custom pricing for multi-campus.</p>
                            <a href="mailto:sales@teach-matrix.app" class="inline-block mt-2 text-sm font-semibold text-blue-700 hover:text-blue-800">sales@teach-matrix.app</a>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-600/10 text-emerald-700 grid place-items-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900">Support</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Bug reports, account access, technical questions.</p>
                            <a href="mailto:support@teach-matrix.app" class="inline-block mt-2 text-sm font-semibold text-emerald-700 hover:text-emerald-800">support@teach-matrix.app</a>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 rounded-xl bg-violet-600/10 text-violet-700 grid place-items-center flex-shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-slate-900">Hours</h3>
                            <p class="text-sm text-slate-600 mt-0.5">Mon–Fri, 9am–6pm Manila time. We aim to reply within one business day.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 rounded-2xl ring-1 ring-blue-200 p-6">
                    <h3 class="font-semibold text-slate-900 mb-1">Already a customer?</h3>
                    <p class="text-sm text-slate-700 mb-3">Sign in to your school's workspace and use in-app chat for faster routing.</p>
                    <a href="{{ route('central.activate.show') }}" class="inline-flex items-center text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Activate or sign in
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </a>
                </div>
            </aside>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="bg-slate-900 text-slate-400">
        <div class="max-w-7xl mx-auto px-6 py-14 grid md:grid-cols-4 gap-8">
            <div class="md:col-span-1">
                <div class="flex items-center gap-2 mb-4">
                    <img src="{{ $appLogo }}" alt="" class="w-8 h-8">
                    <span class="font-bold text-white">{{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance')) }}</span>
                </div>
                <p class="text-sm leading-relaxed">AI-powered faculty evaluation for every school. Built for educators, scaled for institutions.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Product</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="/#features" class="hover:text-white">Features</a></li>
                    <li><a href="/#pricing" class="hover:text-white">Pricing</a></li>
                    <li><a href="/#how" class="hover:text-white">How it works</a></li>
                    <li><a href="{{ route('central.activate.show') }}" class="hover:text-white">Activate</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Company</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="{{ route('central.about') }}" class="hover:text-white">About us</a></li>
                    <li><a href="{{ route('central.contact') }}" class="hover:text-white">Contact us</a></li>
                    <li><a href="/#faq" class="hover:text-white">FAQ</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm">Legal</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#" class="hover:text-white">Privacy policy</a></li>
                    <li><a href="#" class="hover:text-white">Terms of service</a></li>
                    <li><a href="#" class="hover:text-white">Data processing</a></li>
                </ul>
            </div>
        </div>
        <div class="border-t border-slate-800">
            <div class="max-w-7xl mx-auto px-6 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <p>&copy; {{ date('Y') }} {{ \App\Models\Setting::get('app_name', config('app.name', 'Teachers Performance Platform')) }}. All rights reserved.</p>
                <p>Built for schools, by educators.</p>
            </div>
        </div>
    </footer>

</body>
</html>
