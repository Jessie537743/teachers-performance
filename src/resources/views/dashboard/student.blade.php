@extends('layouts.app')

@section('title', 'Student Dashboard')
@section('page-title', 'Student Dashboard')

@section('content')
{{-- Skeleton loading --}}
<div id="skeletonLoader" class="animate-pulse">
    {{-- Header skeleton --}}
    <div class="mb-5">
        <div class="h-7 w-3/4 bg-gray-200 rounded-xl mb-2"></div>
        <div class="h-4 w-1/2 bg-gray-200 rounded-xl"></div>
    </div>
    {{-- Quick stats skeleton --}}
    <div class="grid grid-cols-3 gap-3.5 mb-5">
        @for($i = 0; $i < 3; $i++)
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <div class="h-3 w-24 bg-gray-200 rounded-xl mb-2"></div>
            <div class="h-6 w-10 bg-gray-200 rounded-xl"></div>
        </div>
        @endfor
    </div>
    {{-- Subject card skeletons --}}
    @for($i = 0; $i < 3; $i++)
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-4">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <div class="h-5 w-40 bg-gray-200 rounded-xl skeleton"></div>
                <div class="h-5 w-16 bg-gray-200 rounded-full"></div>
            </div>
            <div class="h-4 w-24 bg-gray-200 rounded-xl"></div>
        </div>
        <div class="px-5 py-4 space-y-3">
            @for($j = 0; $j < 2; $j++)
            <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full bg-gray-200 skeleton"></div>
                    <div>
                        <div class="h-4 w-32 bg-gray-200 rounded-xl mb-1"></div>
                        <div class="h-3 w-40 bg-gray-200 rounded-xl"></div>
                    </div>
                </div>
                <div class="h-7 w-20 bg-gray-200 rounded-lg"></div>
            </div>
            @endfor
        </div>
    </div>
    @endfor
</div>

{{-- Real content (hidden until loaded) --}}
<div id="realContent" style="display:none;">
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $student->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($period)
                Evaluation Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
                &nbsp;<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Open</span>
            @else
                No evaluation period is currently open.
            @endif
        </p>
    </div>
</div>

@if(!$period)
    <div class="bg-amber-50 border border-amber-200 text-amber-800 rounded-xl px-4 py-3 mb-5 text-sm">
        Evaluations are currently closed. Please check back later when the evaluation period is open.
    </div>
@endif

{{-- Quick Stats --}}
@php
    $totalSubjects = $subjectItems->count();
    $totalFaculty  = $subjectItems->sum(fn($item) => $item['faculty_list']->count());
    $completedCount = $subjectItems->sum(fn($item) => $item['faculty_list']->where('has_evaluated', true)->count());
@endphp

<div class="grid grid-cols-3 gap-3.5 mb-5">
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Assigned Subjects</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalSubjects }}</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Evaluations Completed</div>
        <div class="text-2xl font-bold text-gray-900">{{ $completedCount }}</div>
        <div class="text-xs text-gray-400 mt-0.5">Out of {{ $totalFaculty }} faculty</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending Evaluations</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalFaculty - $completedCount }}</div>
    </div>
</div>

{{-- Subject Cards --}}
@forelse($subjectItems as $item)
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-4">
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
            <span class="text-base font-semibold text-gray-900">{{ $item['subject']->title }}</span>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 ml-2">{{ $item['subject']->code }}</span>
        </div>
        <span class="text-sm text-gray-400">
            {{ $item['subject']->semester }} &bull; {{ $item['subject']->school_year }}
        </span>
    </div>
    <div class="px-5 py-4">
        @if($item['faculty_list']->count() > 0)
            <div class="flex flex-col gap-3">
                @foreach($item['faculty_list'] as $fa)
                <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-base flex-shrink-0">
                            {{ strtoupper(substr($fa['faculty_user']->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">{{ $fa['faculty_user']?->name ?? 'Unknown Faculty' }}</div>
                            <div class="text-xs text-gray-400">{{ $fa['faculty_user']?->email ?? '' }}</div>
                        </div>
                    </div>
                    <div>
                        @can('submit-student-evaluation')
                            @if($fa['has_evaluated'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Completed</span>
                            @elseif($period)
                                <a href="{{ route('evaluate.show', ['type' => 'student', 'facultyId' => $fa['faculty_profile']->user_id, 'subjectId' => $item['subject']->id]) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                                    Evaluate
                                </a>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Closed</span>
                            @endif
                        @endcan
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-400 m-0">No faculty assigned to this subject yet.</p>
        @endif
    </div>
</div>
@empty
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
    <div class="text-center text-gray-400 py-12 px-5">
        <div class="text-4xl mb-4">📋</div>
        <div class="text-lg font-semibold mb-2 text-gray-600">No Subjects Assigned</div>
        <p>You have no subjects assigned for evaluation. Please contact your administrator.</p>
    </div>
</div>
@endforelse
</div>{{-- end realContent --}}

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        document.getElementById('skeletonLoader').style.display = 'none';
        document.getElementById('realContent').style.display = 'block';
    }, 300);
});
document.addEventListener('turbo:load', function() {
    var sk = document.getElementById('skeletonLoader');
    var rc = document.getElementById('realContent');
    if (sk) sk.style.display = 'none';
    if (rc) rc.style.display = 'block';
});
</script>
@endpush
@endsection
