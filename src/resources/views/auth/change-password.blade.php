@extends('layouts.app')

@section('title', 'Change Password')
@section('page-title', 'Change Password')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Change Password</h1>
        <p class="text-sm text-gray-500 mt-1">You are required to set a new password before continuing.</p>
    </div>
</div>

<div class="max-w-lg">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-5 py-3.5 border-b border-gray-200">
            <span class="font-semibold text-gray-900">Set New Password</span>
        </div>
        <div class="p-5">
            <form method="POST" action="{{ route('password.update.custom') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="current_password">Current Password</label>
                    <input type="password" name="current_password" id="current_password"
                           class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="Enter your current password" required autocomplete="current-password">
                    @error('current_password')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="password">New Password</label>
                    <input type="password" name="password" id="password"
                           class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="Enter new password (min. 8 characters)" required autocomplete="new-password">
                    @error('password')
                        <span class="text-red-600 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5" for="password_confirmation">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="Repeat new password" required autocomplete="new-password">
                </div>
                <button type="submit" class="w-full mt-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">
                    Update Password
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
