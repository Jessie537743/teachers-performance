@extends('layouts.app')
@section('title', 'Permission Delegations')
@section('page-title', 'Permission Delegations')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Permission Delegations</h1>
        <p class="text-sm text-gray-500 mt-1">Temporarily grant specific permissions to another user with optional time limits.</p>
    </div>
    <a href="{{ route('roles.index') }}" class="inline-flex items-center gap-2 bg-gray-100 text-gray-700 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-200 transition">&larr; Back to Roles</a>
</div>

{{-- Create Delegation Form --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-bold text-slate-800">Create Delegation</h2>
    </div>
    <form method="POST" action="{{ route('roles.delegations.store') }}">
        @csrf
        <div class="p-6 space-y-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Delegator (grants permissions)</label>
                    <select name="delegator_id" required class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select user —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('delegator_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->rolesLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Delegatee (receives permissions)</label>
                    <select name="delegatee_id" required class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Select user —</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" {{ old('delegatee_id') == $u->id ? 'selected' : '' }}>
                                {{ $u->name }} ({{ $u->rolesLabel() }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Starts at (optional)</label>
                    <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">Expires at (optional)</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}"
                           class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Permissions to delegate</label>
                @foreach($permissionGroups as $group => $permissions)
                    <div class="mb-4">
                        <h3 class="text-[13px] font-bold text-slate-700 mb-2 pb-1 border-b border-gray-200">{{ $group }}</h3>
                        <div class="grid grid-cols-[repeat(auto-fill,minmax(260px,1fr))] gap-2">
                            @foreach($permissions as $value => $label)
                                <label class="flex items-center gap-2.5 px-3 py-2 bg-gray-50 rounded-xl cursor-pointer border border-gray-200 hover:bg-blue-50 transition">
                                    <input type="checkbox" name="permissions[]" value="{{ $value }}"
                                           {{ is_array(old('permissions')) && in_array($value, old('permissions')) ? 'checked' : '' }}
                                           class="w-[17px] h-[17px] cursor-pointer accent-blue-600">
                                    <span class="text-sm text-gray-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Create Delegation</button>
            </div>
        </div>
    </form>
</div>

{{-- Existing Delegations --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-bold text-slate-800">Existing Delegations</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-6 py-3 font-semibold">Delegator</th>
                    <th class="px-6 py-3 font-semibold">Delegatee</th>
                    <th class="px-6 py-3 font-semibold">Permissions</th>
                    <th class="px-6 py-3 font-semibold">Window</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($delegations->items() as $d)
                    <tr>
                        <td class="px-6 py-3">
                            <div class="font-semibold text-gray-800">{{ $d->delegator->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $d->delegator?->rolesLabel() ?? '' }}</div>
                        </td>
                        <td class="px-6 py-3">
                            <div class="font-semibold text-gray-800">{{ $d->delegatee->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $d->delegatee?->rolesLabel() ?? '' }}</div>
                        </td>
                        <td class="px-6 py-3">
                            <div class="flex flex-wrap gap-1">
                                @foreach(($d->permissions ?? []) as $p)
                                    <span class="inline-block bg-blue-50 text-blue-700 border border-blue-200 rounded-md px-2 py-0.5 text-xs">{{ $p }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td class="px-6 py-3 text-xs text-gray-600">
                            <div>From: {{ $d->starts_at ? $d->starts_at->format('Y-m-d H:i') : 'immediately' }}</div>
                            <div>Until: {{ $d->expires_at ? $d->expires_at->format('Y-m-d H:i') : 'no expiry' }}</div>
                        </td>
                        <td class="px-6 py-3">
                            @if($d->isActive())
                                <span class="inline-block bg-green-100 text-green-800 border border-green-200 rounded-md px-2 py-0.5 text-xs font-semibold">Active</span>
                            @elseif($d->revoked_at)
                                <span class="inline-block bg-red-100 text-red-800 border border-red-200 rounded-md px-2 py-0.5 text-xs font-semibold">Revoked</span>
                            @elseif($d->expires_at && $d->expires_at->lte(now()))
                                <span class="inline-block bg-gray-100 text-gray-700 border border-gray-200 rounded-md px-2 py-0.5 text-xs font-semibold">Expired</span>
                            @else
                                <span class="inline-block bg-yellow-100 text-yellow-800 border border-yellow-200 rounded-md px-2 py-0.5 text-xs font-semibold">Pending</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            @if($d->revoked_at === null)
                                <form method="POST" action="{{ route('roles.delegations.revoke', $d) }}"
                                      onsubmit="event.preventDefault(); showConfirm('Revoke this delegation?', this, {confirmText: 'Revoke'})">
                                    @csrf
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-semibold text-sm">Revoke</button>
                                </form>
                            @else
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-gray-500">No delegations yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($delegations->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $delegations->links() }}
        </div>
    @endif
</div>
@endsection
