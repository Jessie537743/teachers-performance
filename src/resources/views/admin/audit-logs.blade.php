@extends('layouts.app')
@section('title', 'Audit Trail')
@section('page-title', 'Audit Trail')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Audit Trail</h1>
        <p class="text-sm text-gray-500 mt-1">Review all system activity, user actions, and data changes.</p>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 gap-3.5 mb-5">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-100 text-blue-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Events</div>
            <div class="text-2xl font-bold text-gray-900">{{ number_format($stats['total']) }}</div>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-green-100 text-green-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Today's Events</div>
            <div class="text-2xl font-bold text-green-700">{{ number_format($stats['today']) }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <form method="GET" action="{{ route('audit-logs.index') }}" class="p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="User name or description..."
                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Action</label>
                <select name="action" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All actions</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ ucfirst($act) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Model Type</label>
                <select name="model_type" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All models</option>
                    @foreach($modelTypes as $type)
                        <option value="{{ $type }}" {{ request('model_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-[130px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Filter</button>
                <a href="{{ route('audit-logs.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Clear</a>
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-6 py-3 font-semibold">Timestamp</th>
                    <th class="px-6 py-3 font-semibold">User</th>
                    <th class="px-6 py-3 font-semibold">Action</th>
                    <th class="px-6 py-3 font-semibold">Model</th>
                    <th class="px-6 py-3 font-semibold">Description</th>
                    <th class="px-6 py-3 font-semibold">IP Address</th>
                    <th class="px-6 py-3 font-semibold text-right">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($logs as $log)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-6 py-3">
                            <div class="font-medium text-slate-900">{{ $log->user_name }}</div>
                            @if($log->user_roles)
                                <div class="text-xs text-gray-400">{{ $log->user_roles }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $log->actionBadgeClass() }}">{{ ucfirst($log->action) }}</span>
                        </td>
                        <td class="px-6 py-3 text-gray-600 whitespace-nowrap">
                            @if($log->model_type)
                                {{ $log->model_type }}@if($log->model_id) <span class="text-gray-400">#{{ $log->model_id }}</span>@endif
                            @else
                                <span class="text-gray-400">&mdash;</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-600 max-w-xs truncate">{{ $log->description }}</td>
                        <td class="px-6 py-3 text-gray-500 whitespace-nowrap font-mono text-xs">{{ $log->ip_address ?? '&mdash;' }}</td>
                        <td class="px-6 py-3 text-right">
                            @if($log->old_values || $log->new_values)
                                <button onclick="this.closest('tr').nextElementSibling.classList.toggle('hidden')" class="text-blue-600 hover:text-blue-800 text-xs font-semibold">Details</button>
                            @else
                                <span class="text-gray-300 text-xs">&mdash;</span>
                            @endif
                        </td>
                    </tr>
                    @if($log->old_values || $log->new_values)
                        <tr class="hidden">
                            <td colspan="7" class="px-6 py-4 bg-gray-50">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @if($log->old_values)
                                        <div>
                                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Old Values</div>
                                            <pre class="bg-white border border-gray-200 rounded-xl p-3 text-xs text-gray-700 overflow-x-auto max-h-60">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                    @if($log->new_values)
                                        <div>
                                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">New Values</div>
                                            <pre class="bg-white border border-gray-200 rounded-xl p-3 text-xs text-gray-700 overflow-x-auto max-h-60">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">No audit log entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $logs->links() }}
        </div>
    @endif
</div>
@endsection
