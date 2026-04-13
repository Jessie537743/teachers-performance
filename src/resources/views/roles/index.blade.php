@extends('layouts.app')
@section('title', 'Roles & Permissions')
@section('page-title', 'Roles & Permissions')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Roles &amp; Permissions</h1>
        <p class="text-sm text-gray-500 mt-1">Configure what each user role can access in the system.</p>
    </div>
</div>

<div class="flex gap-4 items-center mb-6 flex-wrap">
    <a href="{{ route('roles.delegations.index') }}" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><path d="M20 8v6"/><path d="M23 11h-6"/></svg>
        Manage Delegations
    </a>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    {{-- Role tab strip --}}
    <div class="px-0 py-0 border-b border-gray-200 overflow-x-auto">
        <div class="flex gap-0 min-w-max">
            @foreach($roles as $index => $role)
                <button type="button"
                    class="role-tab {{ $index === 0 ? 'active' : '' }}"
                    onclick="showRoleTab('{{ $role }}')"
                    id="tab-{{ $role }}"
                    style="padding:12px 20px;border:none;border-bottom:3px solid {{ $index === 0 ? '#2563eb' : 'transparent' }};background:transparent;color:{{ $index === 0 ? '#2563eb' : '#374151' }};cursor:pointer;font-weight:600;font-size:13px;white-space:nowrap;transition:all .15s;">
                    {{ \App\Enums\Permission::roleLabel($role) }}
                </button>
            @endforeach
        </div>
    </div>

    {{-- One panel per role --}}
    @foreach($roles as $index => $role)
    <div class="role-panel" id="panel-{{ $role }}" {!! $index !== 0 ? 'style="display:none;"' : '' !!}>
        <form method="POST" action="{{ route('roles.update') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="role" value="{{ $role }}">

            <div class="p-6">

                @if($role === 'admin')
                    <div class="bg-blue-100 border border-blue-300 rounded-xl px-4 py-3.5 mb-5 text-blue-800 text-sm">
                        <strong>Note:</strong> The Administrator role has a system-level bypass and can access everything regardless of these settings.
                    </div>
                @endif

                @foreach($permissionGroups as $group => $permissions)
                    <div class="mb-6">
                        <h3 class="text-[15px] font-bold text-slate-800 mb-3 pb-2 border-b-2 border-gray-200">
                            {{ $group }}
                        </h3>
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-2.5">
                            @foreach($permissions as $value => $label)
                                <label class="flex items-center gap-2.5 px-3.5 py-2.5 bg-gray-50 rounded-xl cursor-pointer border border-gray-200 transition hover:bg-blue-50">
                                    <input type="checkbox"
                                           name="permissions[]"
                                           value="{{ $value }}"
                                           {{ in_array($value, $rolePermissions[$role] ?? []) ? 'checked' : '' }}
                                           class="w-[18px] h-[18px] cursor-pointer accent-blue-600">
                                    <span class="text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="flex gap-3 pt-4 border-t border-gray-200">
                    <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">
                        Save {{ \App\Enums\Permission::roleLabel($role) }} Permissions
                    </button>
                </div>
            </div>
        </form>
    </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
function showRoleTab(role) {
    document.querySelectorAll('.role-panel').forEach(function(p) {
        p.style.display = 'none';
    });
    document.querySelectorAll('.role-tab').forEach(function(t) {
        t.style.color = '#374151';
        t.style.borderBottom = '3px solid transparent';
        t.classList.remove('active');
    });

    document.getElementById('panel-' + role).style.display = 'block';

    var tab = document.getElementById('tab-' + role);
    tab.style.color = '#2563eb';
    tab.style.borderBottom = '3px solid #2563eb';
    tab.classList.add('active');
}
</script>
@endpush
