<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Register — {{ \App\Models\Setting::get('app_name', config('app.name')) }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', system-ui, sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen py-10 px-4 text-slate-800">
<div class="max-w-3xl mx-auto">
    <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-slate-700">&larr; Back to sign in</a>

    <div class="mt-4 bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-8">
        <div class="flex items-center gap-3 mb-6">
            <img src="{{ $appLogo }}" alt="" class="w-10 h-10 rounded-lg">
            <div>
                <h1 class="text-xl font-bold text-slate-900">Create your account</h1>
                <p class="text-sm text-slate-500">Submit your details — your registration goes to {{ ($kind === 'student' || ! $kind) ? 'your dean' : 'an administrator' }} for approval.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-lg bg-rose-50 border border-rose-200 p-3 text-sm text-rose-700">
                <p class="font-semibold mb-1">Please fix the errors below:</p>
                <ul class="list-disc ml-5">
                    @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
                </ul>
            </div>
        @endif

        @if (! $kind)
            <div class="grid sm:grid-cols-2 gap-3 mb-6">
                <a href="{{ route('register.show', ['kind' => 'student']) }}" class="rounded-xl border-2 border-slate-200 hover:border-blue-400 hover:bg-blue-50/50 p-5 transition">
                    <div class="text-2xl mb-2">🎓</div>
                    <h3 class="font-semibold text-slate-900">I'm a student</h3>
                    <p class="text-xs text-slate-500 mt-1">Routes to your dean for approval.</p>
                </a>
                <a href="{{ route('register.show', ['kind' => 'personnel']) }}" class="rounded-xl border-2 border-slate-200 hover:border-blue-400 hover:bg-blue-50/50 p-5 transition">
                    <div class="text-2xl mb-2">👔</div>
                    <h3 class="font-semibold text-slate-900">I'm faculty / personnel</h3>
                    <p class="text-xs text-slate-500 mt-1">Routes to an administrator for approval.</p>
                </a>
            </div>
        @else
            <p class="text-xs text-slate-500 mb-4">
                Registering as <strong class="text-slate-700">{{ $kind === 'student' ? 'Student' : 'Personnel' }}</strong> ·
                <a href="{{ route('register.show') }}" class="text-blue-600 hover:underline">change</a>
            </p>

            <form method="POST" action="{{ route('register.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="kind" value="{{ $kind }}">

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
                        <input type="text" name="name" value="{{ old('name') }}" required maxlength="191"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                        <input type="email" name="email" value="{{ old('email') }}" required maxlength="191"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                    </div>
                </div>

                <div class="grid sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                        <input type="password" name="password" required minlength="8"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                        <p class="mt-1 text-xs text-slate-500">At least 8 characters.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                        <input type="password" name="password_confirmation" required minlength="8"
                               class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Department</label>
                    <select name="department_id" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                        <option value="">— Select your department —</option>
                        @foreach ($departments as $dept)
                            <option value="{{ $dept->id }}" @selected(old('department_id') == $dept->id)>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($kind === 'student')
                    <fieldset class="space-y-4 pt-4 border-t border-slate-200">
                        <legend class="text-sm font-semibold text-slate-900 mb-2">Academic details</legend>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Course</label>
                                <input type="text" name="course" value="{{ old('course') }}" required maxlength="120"
                                       placeholder="BS Computer Science"
                                       class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Year level</label>
                                <select name="year_level" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                                    <option value="">— Select —</option>
                                    @foreach (['1st Year','2nd Year','3rd Year','4th Year','5th Year'] as $yl)
                                        <option value="{{ $yl }}" @selected(old('year_level') === $yl)>{{ $yl }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Section</label>
                                <input type="text" name="section" value="{{ old('section') }}" required maxlength="50"
                                       class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                                <select name="student_status" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                                    @foreach (['regular','irregular'] as $st)
                                        <option value="{{ $st }}" @selected(old('student_status') === $st)>{{ ucfirst($st) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">School year</label>
                                <input type="text" name="school_year" value="{{ old('school_year') }}" required maxlength="50"
                                       placeholder="2025-2026"
                                       class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Semester</label>
                                <select name="semester" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                                    <option value="">— Select —</option>
                                    @foreach (['1st Semester','2nd Semester','Summer'] as $sm)
                                        <option value="{{ $sm }}" @selected(old('semester') === $sm)>{{ $sm }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </fieldset>
                @else
                    <fieldset class="space-y-4 pt-4 border-t border-slate-200">
                        <legend class="text-sm font-semibold text-slate-900 mb-2">Personnel details</legend>
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
                                <select name="role" required class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                                    <option value="">— Select role —</option>
                                    @foreach ($roles as $r)
                                        <option value="{{ $r }}" @selected(old('role') === $r)>{{ ucwords(str_replace('_', ' ', $r)) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-slate-700 mb-1">Department position</label>
                                <input type="text" name="department_position" value="{{ old('department_position') }}" required maxlength="40"
                                       placeholder="e.g. Faculty, Senior Faculty, Department Head"
                                       class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500/30">
                            </div>
                        </div>
                        <p class="text-xs text-slate-500">Your account will be reviewed by the platform administrator and human resources.</p>
                    </fieldset>
                @endif

                <button type="submit" class="w-full rounded-lg bg-gradient-to-r from-blue-700 to-blue-900 hover:from-blue-800 hover:to-blue-950 text-white py-3 text-sm font-semibold shadow-lg shadow-blue-900/20 transition">
                    Submit registration
                </button>

                <p class="text-xs text-slate-500 text-center">
                    By submitting, you agree to wait for {{ $kind === 'student' ? 'your dean' : 'an administrator' }} to review your application.
                </p>
            </form>
        @endif
    </div>
</div>
</body>
</html>
