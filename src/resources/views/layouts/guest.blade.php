<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ \App\Models\Setting::get('app_name', 'Evaluation System') }}</title>

    {{-- Favicon: SVG primary, ICO fallback, Apple touch icon for iOS. --}}
    <link rel="icon" type="image/svg+xml" href="{{ asset(config('app.default_logo', 'images/default-logo.svg')) }}">
    <link rel="alternate icon" href="{{ asset('favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset(config('app.default_logo', 'images/default-logo.svg')) }}">

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
