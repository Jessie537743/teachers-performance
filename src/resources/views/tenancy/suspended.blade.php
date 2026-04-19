@php
    $messages = [
        'suspended'           => 'This school is currently suspended. Contact your platform administrator if this is unexpected.',
        'pending_activation'  => 'This school hasn\'t been activated yet. Visit ' . url('/', secure: false) . '/activate on the platform to redeem your code.',
        'failed'              => 'This school failed to provision. Contact your platform administrator.',
        'provisioning'        => 'This school is being set up. Please try again in a moment.',
    ];
    $message = $messages[$tenant->status] ?? 'This school is unavailable right now.';

    $titleStatus = match ($tenant->status) {
        'pending_activation' => 'awaiting activation',
        default              => $tenant->status,
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $tenant->name }} — {{ $titleStatus }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <div class="bg-white shadow rounded-lg p-8 max-w-md text-center">
        <h1 class="text-xl font-semibold text-slate-900 mb-2">{{ $tenant->name }} is {{ $titleStatus }}.</h1>
        <p class="text-sm text-slate-600">{{ $message }}</p>

        @if ($tenant->status === 'pending_activation')
            <a href="{{ url('http://localhost:8081/activate') }}" class="mt-6 inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Go to activation
            </a>
        @endif
    </div>
</body>
</html>
