@extends('layouts.app')
@section('title', 'Settings')
@section('page-title', 'Settings')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Settings</h1>
        <p class="text-sm text-gray-500 mt-1">Manage application settings and view all users.</p>
    </div>
</div>

{{-- Tab Navigation --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-6">
    <div class="flex gap-0 border-b border-gray-200">
        <a href="{{ route('settings.index', ['tab' => 'general']) }}"
           class="px-6 py-3.5 font-semibold text-sm no-underline transition {{ $tab === 'general' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            General
        </a>
        <a href="{{ route('settings.index', ['tab' => 'users']) }}"
           class="px-6 py-3.5 font-semibold text-sm no-underline transition {{ $tab === 'users' ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            Users
        </a>
        <a href="{{ route('roles.index') }}"
           class="px-6 py-3.5 font-semibold text-sm no-underline transition {{ request()->routeIs('roles.*') ? 'text-blue-600 border-b-[3px] border-blue-600' : 'text-gray-700 border-b-[3px] border-transparent hover:text-blue-600' }}">
            Roles & Permissions
        </a>
    </div>

    {{-- General Tab --}}
    @if($tab === 'general')
    <div class="p-6">
        <form method="POST" action="{{ route('settings.update-general') }}" enctype="multipart/form-data">
            @csrf

            {{-- App Name --}}
            <div class="mb-4 max-w-[480px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="app_name">Application Name</label>
                <input type="text" name="app_name" id="app_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       value="{{ old('app_name', $appName) }}" required maxlength="100"
                       placeholder="e.g. Evaluation System">
                <div class="text-xs text-gray-400 mt-1">
                    Displayed in the sidebar, page titles, and login page.
                </div>
            </div>

            {{-- App Logo --}}
            <div class="mb-4 max-w-[480px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Application Logo</label>

                {{-- Current Logo Preview --}}
                <div class="flex items-center gap-4 mb-3 p-4 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="w-16 h-16 rounded-xl bg-white border border-gray-200 grid place-items-center overflow-hidden shrink-0">
                        @if($appLogo)
                            <img src="{{ asset('storage/' . $appLogo) }}" alt="App Logo"
                                 class="max-w-[56px] max-h-[56px] object-contain">
                        @else
                            <img src="{{ asset('images/smcc_logo.png') }}" alt="Default Logo"
                                 class="max-w-[56px] max-h-[56px] object-contain">
                        @endif
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-slate-800">
                            {{ $appLogo ? 'Custom Logo' : 'Default Logo' }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $appLogo ? 'Using uploaded logo' : 'Using default SMCC logo' }}
                        </div>
                    </div>
                    @if($appLogo)
                    <form method="POST" action="{{ route('settings.remove-logo') }}" class="ml-auto"
                          onsubmit="return confirm('Remove the custom logo? The default logo will be used instead.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold hover:bg-red-700 transition">Remove</button>
                    </form>
                    @endif
                </div>

                <input type="file" name="app_logo" id="app_logo" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       accept="image/png,image/jpeg,image/svg+xml,image/webp">
                <div class="text-xs text-gray-400 mt-1">
                    Accepted formats: PNG, JPG, SVG, WebP. Max size: 2MB.
                </div>
            </div>

            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Settings</button>
        </form>
    </div>
    @endif

    {{-- Users Tab --}}
    @if($tab === 'users')
    <div class="p-6">
        {{-- Filters --}}
        <form method="GET" action="{{ route('settings.index') }}" class="flex gap-3 items-end flex-wrap mb-5">
            <input type="hidden" name="tab" value="users">
            <div class="m-0 min-w-[200px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="search">Search</label>
                <input type="text" name="search" id="search" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       value="{{ $search }}" placeholder="Name or email...">
            </div>
            <div class="m-0 min-w-[160px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="role">Role</label>
                <select name="role" id="role" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">All Roles</option>
                    @foreach(\App\Enums\Permission::allRoles() as $role)
                        <option value="{{ $role }}" {{ $roleFilter === $role ? 'selected' : '' }}>
                            {{ \App\Enums\Permission::roleLabel($role) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Filter</button>
            <a href="{{ route('settings.index', ['tab' => 'users']) }}" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Reset</a>
        </form>

        {{-- Users Table --}}
        <div class="overflow-x-auto">
            <table class="w-full border-collapse min-w-[700px]">
                <thead>
                    <tr>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Email</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Role</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Status</th>
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Joined</th>
                        @if(auth()->user()->isAdmin())
                        <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                    <tr class="hover:bg-blue-50/50 transition-colors">
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($users->currentPage() - 1) * $users->perPage() + $loop->iteration }}</td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-blue-600 text-white grid place-items-center font-bold text-xs shrink-0">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </div>
                                <strong>{{ $user->name }}</strong>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle text-sm text-gray-400">{{ $user->email }}</td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700 capitalize">
                                {{ str_replace('_', ' ', $user->role) }}
                            </span>
                        </td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle text-sm">{{ $user->department?->name ?? '—' }}</td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                            @if($user->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle text-sm text-gray-400">{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                        @if(auth()->user()->isAdmin())
                        <td class="px-4 py-3.5 border-b border-gray-200 align-middle text-right">
                            <button type="button"
                                    onclick="openResetModal({{ $user->id }}, @js($user->name))"
                                    class="inline-flex items-center gap-1.5 bg-amber-500 text-white px-3 py-1.5 rounded-lg text-xs font-semibold hover:bg-amber-600 transition">
                                Reset Password
                            </button>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ auth()->user()->isAdmin() ? 8 : 7 }}" class="text-center text-gray-500 py-8 px-4">
                            No users found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users instanceof \Illuminate\Pagination\LengthAwarePaginator && $users->hasPages())
        <div class="py-4">
            {{ $users->links() }}
        </div>
        @endif
    </div>
    @endif
</div>

@if(auth()->user()->isAdmin())
{{-- Reset Password Modal --}}
<div id="resetPasswordModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
        <form id="resetPasswordForm" method="POST" action="">
            @csrf
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-slate-900">Reset Password</h3>
                <p class="text-sm text-gray-500 mt-1">Set a new password for <strong id="resetUserName" class="text-slate-800"></strong>. They will be required to change it on next login.</p>
            </div>
            <div class="px-6 py-5 space-y-4">
                @if($errors->any())
                    <div class="bg-red-100 border border-red-300 rounded-xl px-3 py-2 text-red-800 text-sm">
                        @foreach($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">New Password</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Confirm Password</label>
                    <input type="password" name="password_confirmation" required minlength="8"
                           class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                </div>
            </div>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-2">
                <button type="button" onclick="closeResetModal()" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2 rounded-xl font-semibold hover:bg-gray-300 transition">Cancel</button>
                <button type="submit" class="inline-flex items-center gap-2 bg-amber-500 text-white px-4 py-2 rounded-xl font-semibold hover:bg-amber-600 transition">Reset Password</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@if(auth()->user()->isAdmin())
@push('scripts')
<script>
function openResetModal(userId, userName) {
    const modal = document.getElementById('resetPasswordModal');
    const form  = document.getElementById('resetPasswordForm');
    form.action = '{{ url('settings/users') }}/' + userId + '/reset-password';
    document.getElementById('resetUserName').textContent = userName;
    form.reset();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}
function closeResetModal() {
    const modal = document.getElementById('resetPasswordModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}
document.getElementById('resetPasswordModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeResetModal();
});
@if($errors->any() && old('_token'))
    // Reopen modal on validation error (best-effort — we don't know which user)
@endif
</script>
@endpush
@endif
