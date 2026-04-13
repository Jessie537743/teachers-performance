@extends('layouts.app')
@section('title', 'Password Reset Requests')
@section('page-title', 'Password Reset Requests')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Password Reset Requests</h1>
        <p class="text-sm text-gray-500 mt-1">Review and manage user password reset requests.</p>
    </div>
    @if($pendingCount > 0)
        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold bg-amber-100 text-amber-700">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            {{ $pendingCount }} pending
        </span>
    @endif
</div>

{{-- Filters --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <form method="GET" action="{{ route('password-reset-requests.index') }}" class="p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="User name or email..."
                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All statuses</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="declined" {{ request('status') === 'declined' ? 'selected' : '' }}>Declined</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Filter</button>
                <a href="{{ route('password-reset-requests.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Clear</a>
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200 bg-gray-50/80">
                    <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Requested At</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">User</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Status</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">IP Address</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Reviewed By</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600 text-xs uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($requests as $req)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                            {{ $req->created_at?->format('M d, Y g:i A') ?? '—' }}
                        </td>
                        <td class="px-4 py-3">
                            @if($req->user)
                                <div class="font-medium text-gray-900">{{ $req->user->name }}</div>
                                <div class="text-xs text-gray-500">{{ $req->user->email }}</div>
                                <div class="flex gap-1 mt-1">
                                    @foreach($req->user->roles ?? [] as $role)
                                        <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold rounded bg-blue-100 text-blue-700">{{ ucfirst($role) }}</span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-gray-400">Deleted user</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $req->statusBadgeClass() }}">
                                {{ ucfirst($req->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs font-mono">{{ $req->ip_address ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($req->reviewer)
                                <div>{{ $req->reviewer->name }}</div>
                                <div class="text-xs text-gray-400">{{ $req->reviewed_at?->format('M d, Y g:i A') }}</div>
                            @else
                                <span class="text-gray-400">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if($req->isPending())
                                <div class="flex items-center justify-end gap-2">
                                    {{-- Approve --}}
                                    <form method="POST" action="{{ route('password-reset-requests.approve', $req) }}" onsubmit="return confirm('Approve this password reset request?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                            Approve
                                        </button>
                                    </form>

                                    {{-- Decline --}}
                                    <button type="button" onclick="toggleDeclineForm({{ $req->id }})" class="inline-flex items-center gap-1 px-3 py-1.5 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                        Decline
                                    </button>
                                </div>

                                {{-- Decline form (hidden) --}}
                                <div id="decline-form-{{ $req->id }}" class="hidden mt-2">
                                    <form method="POST" action="{{ route('password-reset-requests.decline', $req) }}" class="flex flex-col items-end gap-2">
                                        @csrf
                                        <textarea name="admin_notes" rows="2" placeholder="Reason for declining (optional)..."
                                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-xs focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
                                        <div class="flex gap-2">
                                            <button type="button" onclick="toggleDeclineForm({{ $req->id }})" class="px-3 py-1 bg-gray-100 text-gray-600 text-xs font-semibold rounded-lg hover:bg-gray-200 transition">Cancel</button>
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs font-semibold rounded-lg hover:bg-red-700 transition">Confirm Decline</button>
                                        </div>
                                    </form>
                                </div>
                            @elseif($req->admin_notes)
                                <span class="text-xs text-gray-500 italic" title="{{ $req->admin_notes }}">Notes: {{ Str::limit($req->admin_notes, 30) }}</span>
                            @else
                                <span class="text-gray-400">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-gray-300"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                                <span>No password reset requests found.</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($requests->hasPages())
        <div class="px-4 py-3 border-t border-gray-200">
            {{ $requests->links() }}
        </div>
    @endif
</div>

<script>
function toggleDeclineForm(id) {
    var el = document.getElementById('decline-form-' + id);
    el.classList.toggle('hidden');
}
</script>
@endsection
