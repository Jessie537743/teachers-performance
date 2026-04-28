@extends('layouts.app')

@section('title', 'Registration approvals')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6">
    <div class="flex flex-wrap items-end justify-between gap-3 mb-1">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Registration approvals</h1>
            <p class="text-sm text-slate-500 mt-1">{{ $scopeMessage }}</p>
        </div>

        @auth
            @if (auth()->user()->hasRole(['admin', 'human_resource']))
                <div class="inline-flex rounded-lg p-1 bg-slate-100 ring-1 ring-slate-200">
                    <a href="{{ route('admin.registration-approvals.index', ['kind' => 'personnel', 'status' => $statusFilter]) }}"
                       class="px-4 py-1.5 rounded-md text-sm font-semibold transition {{ $kind === 'personnel' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900' }}">
                        Personnel
                    </a>
                    <a href="{{ route('admin.registration-approvals.index', ['kind' => 'student', 'status' => $statusFilter]) }}"
                       class="px-4 py-1.5 rounded-md text-sm font-semibold transition {{ $kind === 'student' ? 'bg-white shadow-sm text-slate-900' : 'text-slate-600 hover:text-slate-900' }}">
                        Student
                    </a>
                </div>
            @endif
        @endauth
    </div>

    @if (session('status'))
        <div class="my-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    {{-- Status filter pills --}}
    <div class="flex flex-wrap items-center gap-2 mt-5 mb-4">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label)
            <a href="{{ route('admin.registration-approvals.index', ['kind' => $kind, 'status' => $key]) }}"
               class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold transition {{ $statusFilter === $key ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50' }}">
                {{ $label }}
                <span class="inline-flex items-center justify-center min-w-[1.25rem] px-1 rounded-full text-[10px] font-bold {{ $statusFilter === $key ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">
                    {{ $counts[$key] ?? 0 }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Applicant</th>
                        <th class="px-6 py-3">Department</th>
                        <th class="px-6 py-3">Details</th>
                        <th class="px-6 py-3">Submitted</th>
                        <th class="px-6 py-3 text-right">{{ $statusFilter === 'pending' ? 'Actions' : 'Decision' }}</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100 text-sm">
                    @forelse ($requests as $req)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-6 py-3.5">
                                <div class="font-semibold text-slate-900">{{ $req->name }}</div>
                                <div class="text-xs text-slate-500">{{ $req->email }}</div>
                                <div class="text-[10px] text-slate-400 font-mono mt-0.5">REG-{{ str_pad((string) $req->id, 6, '0', STR_PAD_LEFT) }}</div>
                            </td>
                            <td class="px-6 py-3.5 text-slate-700">{{ $req->department?->name ?? '—' }}</td>
                            <td class="px-6 py-3.5 text-xs text-slate-600">
                                @if ($req->kind === 'student')
                                    <div><span class="text-slate-500">Course:</span> {{ $req->payload['course'] ?? '—' }}</div>
                                    <div><span class="text-slate-500">Year/Section:</span> {{ $req->payload['year_level'] ?? '—' }} · {{ $req->payload['section'] ?? '—' }}</div>
                                    <div><span class="text-slate-500">Term:</span> {{ $req->payload['semester'] ?? '—' }}, {{ $req->payload['school_year'] ?? '—' }}</div>
                                @else
                                    <div><span class="text-slate-500">Role:</span> {{ ucwords(str_replace('_', ' ', $req->payload['role'] ?? '—')) }}</div>
                                    <div><span class="text-slate-500">Position:</span> {{ $req->payload['department_position'] ?? '—' }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-3.5 text-xs text-slate-500 whitespace-nowrap">{{ $req->created_at?->diffForHumans() }}</td>
                            <td class="px-6 py-3.5 text-right">
                                @if ($req->status === 'pending')
                                    <div class="inline-flex items-center gap-2">
                                        <form method="POST" action="{{ route('admin.registration-approvals.approve', $req) }}" onsubmit="return confirm('Approve {{ $req->name }} ({{ $req->email }})? This creates the user account immediately.');">
                                            @csrf
                                            <button class="inline-flex items-center rounded-md bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold px-3 py-1.5">Approve</button>
                                        </form>
                                        <button type="button"
                                                onclick="document.getElementById('reject-{{ $req->id }}').classList.remove('hidden')"
                                                class="inline-flex items-center rounded-md border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-semibold px-3 py-1.5">Reject</button>
                                    </div>
                                @elseif ($req->status === 'approved')
                                    <div class="text-xs text-emerald-700 font-medium">
                                        Approved by {{ $req->decider?->name ?? 'system' }}
                                        <div class="text-[10px] text-slate-500">{{ $req->decided_at?->diffForHumans() }}</div>
                                    </div>
                                @else
                                    <div class="text-xs text-rose-700 font-medium">
                                        Rejected by {{ $req->decider?->name ?? 'system' }}
                                        <div class="text-[10px] text-slate-500">{{ $req->decided_at?->diffForHumans() }}</div>
                                        @if ($req->reason)
                                            <div class="text-[11px] text-slate-600 mt-1 max-w-xs">"{{ \Illuminate\Support\Str::limit($req->reason, 90) }}"</div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        </tr>
                        {{-- Reject reason form (hidden by default) --}}
                        @if ($req->status === 'pending')
                            <tr id="reject-{{ $req->id }}" class="hidden bg-rose-50/50">
                                <td colspan="5" class="px-6 py-3">
                                    <form method="POST" action="{{ route('admin.registration-approvals.reject', $req) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="text" name="reason" required maxlength="500" placeholder="Reason (sent to applicant) — e.g. invalid course, not in this department"
                                               class="flex-1 min-w-[280px] rounded-lg border-rose-300 text-sm focus:border-rose-500 focus:ring-rose-500/30">
                                        <button class="inline-flex items-center rounded-md bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold px-3 py-1.5">Confirm reject</button>
                                        <button type="button" onclick="document.getElementById('reject-{{ $req->id }}').classList.add('hidden')"
                                                class="inline-flex items-center rounded-md border border-slate-300 text-slate-700 hover:bg-slate-100 text-xs px-3 py-1.5">Cancel</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="5" class="px-6 py-12 text-center text-sm text-slate-500">No {{ $statusFilter }} {{ $kind }} registrations.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($requests->hasPages())
            <div class="px-6 py-3 border-t border-slate-100 text-xs text-slate-600">{{ $requests->links() }}</div>
        @endif
    </div>
</div>
@endsection
