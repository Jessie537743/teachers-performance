@extends('super-admin.layout', ['title' => 'Sign in'])

@section('content')
<div class="max-w-md mx-auto bg-white shadow rounded-lg p-8 mt-12">
    <h1 class="text-2xl font-semibold text-slate-900 mb-1">Platform Console</h1>
    <p class="text-sm text-slate-500 mb-6">Sign in to manage schools.</p>

    <form method="POST" action="{{ route('admin.login.attempt') }}" class="space-y-4">
        @csrf
        <div>
            <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
        </div>
        <div>
            <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
            <input id="password" name="password" type="password" required
                class="w-full rounded-md border-slate-300 shadow-sm focus:border-slate-500 focus:ring-slate-500">
        </div>
        @error('email')
            <p class="text-sm text-red-600">{{ $message }}</p>
        @enderror
        <button type="submit" class="w-full rounded-md bg-slate-900 text-white py-2 hover:bg-slate-800">
            Sign in
        </button>
    </form>
</div>
@endsection
