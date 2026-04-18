<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="login-page">
    <div class="max-w-md mx-auto mt-4 px-4">
        @include('layouts.partials.login-announcements')
    </div>
    {{ $slot }}
</body>
</html>
