@extends('layouts.app')

@section('title', 'My Account')
@section('page-title', 'My Account')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">My Account</h1>
        <p class="text-sm text-gray-500 mt-1">View and manage your personal and account details.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
    {{-- Profile Card --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
        <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
            <span class="font-bold text-slate-900">Personal Information</span>
        </div>
        <div class="p-5">
            <div class="flex items-center gap-5 mb-6 pb-5 border-b border-gray-200">
                <div class="w-[72px] h-[72px] rounded-full bg-blue-600 text-white grid place-items-center text-[28px] font-bold shrink-0">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-xl font-bold text-slate-800">{{ $user->name }}</div>
                    <div class="text-sm text-gray-400">{{ $user->email }}</div>
                    <div class="mt-1.5 flex gap-1.5">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 capitalize">{{ str_replace('_', ' ', $user->role) }}</span>
                        @if($user->is_active)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Inactive</span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid gap-4">
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-400 font-medium">Full Name</span>
                    <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->name }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-400 font-medium">Email Address</span>
                    <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->email }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-400 font-medium">Role</span>
                    <span class="text-sm text-slate-800 font-semibold text-right capitalize">{{ str_replace('_', ' ', $user->role) }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-400 font-medium">Department</span>
                    <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->department?->name ?? 'Not assigned' }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-400 font-medium">Account Status</span>
                    <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-400 font-medium">Member Since</span>
                    <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->created_at?->format('F j, Y') ?? '—' }}</span>
                </div>

                @if($user->studentProfile)
                <div class="mt-2 pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-bold text-slate-800 mb-3">Student Details</h4>
                    <div class="grid gap-3">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">Course</span>
                            <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->studentProfile->course }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">Year Level</span>
                            <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->studentProfile->year_level }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">Section</span>
                            <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->studentProfile->section }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">Status</span>
                            <span class="text-sm text-slate-800 font-semibold text-right capitalize">{{ $user->studentProfile->student_status }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">Semester</span>
                            <span class="text-sm text-slate-800 font-semibold text-right">{{ format_semester($user->studentProfile->semester) }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">School Year</span>
                            <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->studentProfile->school_year }}</span>
                        </div>
                    </div>
                </div>
                @endif

                @if($user->facultyProfile)
                <div class="mt-2 pt-4 border-t border-gray-200">
                    <h4 class="text-sm font-bold text-slate-800 mb-3">Faculty Details</h4>
                    <div class="grid gap-3">
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">Faculty Department</span>
                            <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->facultyProfile->department?->name ?? 'Not assigned' }}</span>
                        </div>
                        <div class="flex justify-between items-center py-2">
                            <span class="text-sm text-gray-400 font-medium">Profile Created</span>
                            <span class="text-sm text-slate-800 font-semibold text-right">{{ $user->facultyProfile->created_at?->format('F j, Y') ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Edit Form + Quick Actions --}}
    <div class="grid gap-6">
        {{-- Edit Details --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
            <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
                <span class="font-bold text-slate-900">Edit Details</span>
            </div>
            <div class="p-5">
                <form method="POST" action="{{ route('account.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="acc_name">Full Name</label>
                        <input type="text" name="name" id="acc_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                               value="{{ old('name', $user->name) }}" required maxlength="150">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="acc_email">Email Address</label>
                        <input type="email" name="email" id="acc_email" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                               value="{{ old('email', $user->email) }}" required maxlength="191">
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                </form>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
            <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
                <span class="font-bold text-slate-900">Quick Actions</span>
            </div>
            <div class="p-5">
                <div class="grid gap-2.5">
                    <a href="{{ route('password.change') }}" class="flex items-center gap-3.5 px-4 py-3.5 rounded-xl border border-gray-200 no-underline text-gray-700 transition hover:bg-gray-50 hover:border-blue-300">
                        <svg class="text-blue-600 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                        <div>
                            <div class="font-semibold text-sm">Change Password</div>
                            <div class="text-xs text-gray-400">Update your account password</div>
                        </div>
                    </a>
                    @can('manage-settings')
                    <a href="{{ route('settings.index') }}" class="flex items-center gap-3.5 px-4 py-3.5 rounded-xl border border-gray-200 no-underline text-gray-700 transition hover:bg-gray-50 hover:border-blue-300">
                        <svg class="text-blue-600 shrink-0" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        <div>
                            <div class="font-semibold text-sm">Roles & Permissions</div>
                            <div class="text-xs text-gray-400">Manage user role access settings</div>
                        </div>
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
