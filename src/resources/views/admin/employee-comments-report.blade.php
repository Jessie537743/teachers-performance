@extends('layouts.app')

@section('title', 'Employee Comments Report')
@section('page-title', 'Employee Comments Report')

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .print-hide { display: none !important; }
        .print-only { display: block !important; }
        aside,
        .sidebar-overlay-bg,
        #formLoadingOverlay,
        main > header {
            display: none !important;
        }
        main { margin-left: 0 !important; }
        main > section {
            padding: 0 !important;
            margin: 0 !important;
        }
        body { background: white !important; }
        .report-shell,
        .report-shell * {
            color: #111827 !important;
            box-shadow: none !important;
            text-shadow: none !important;
        }
        .report-shell .bg-white,
        .report-shell [class*="bg-"] {
            background: #fff !important;
        }
        .report-shell .rounded-2xl,
        .report-shell .rounded-lg,
        .report-shell .rounded-full {
            border-radius: 0 !important;
        }
        .report-shell .border {
            border-color: #d1d5db !important;
        }
        .report-header-card {
            border: 0 !important;
            padding: 0 !important;
            margin-bottom: 14px !important;
        }
        .report-employee-card {
            border: 0 !important;
            padding: 0 0 8px 0 !important;
            margin-bottom: 10px !important;
            border-bottom: 1px solid #d1d5db !important;
        }
        .comment-level-card {
            margin-bottom: 12px !important;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .comment-item {
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .comment-meta {
            display: block !important;
        }
        .comment-meta-right {
            margin-top: 2px !important;
            display: block !important;
        }
        p, div, span {
            overflow-wrap: break-word !important;
            word-break: normal !important;
        }
        @page {
            size: A4;
            margin: 14mm 12mm;
        }
    }

    .print-only { display: none; }
</style>
@endpush

@section('content')
<div class="mb-5 animate-slide-up no-print">
    <h1 class="text-2xl font-bold text-gray-900">Employee Comments & Classification</h1>
    <p class="mt-1 text-sm text-gray-500">Generate all comments for a selected employee and classify them by performance level and sentiment (positive, negative, neutral).</p>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 mb-5 no-print">
    <form method="GET" action="{{ route('reports.employee-comments') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="faculty_profile_id" class="block text-sm font-medium text-gray-700 mb-1">Employee</label>
            <select id="faculty_profile_id" name="faculty_profile_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">Select employee</option>
                @foreach($facultyOptions as $faculty)
                    <option value="{{ $faculty->id }}" @selected((string) request('faculty_profile_id') === (string) $faculty->id)>
                        {{ $faculty->user?->name ?? 'Faculty' }} ({{ $faculty->department?->code ?? 'No Dept' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
            <select id="semester" name="semester" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">All semesters</option>
                @foreach($periods->pluck('semester')->filter()->unique() as $semester)
                    <option value="{{ $semester }}" @selected(request('semester') === $semester)>{{ $semester }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="school_year" class="block text-sm font-medium text-gray-700 mb-1">School Year</label>
            <select id="school_year" name="school_year" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">All school years</option>
                @foreach($periods->pluck('school_year')->filter()->unique() as $schoolYear)
                    <option value="{{ $schoolYear }}" @selected(request('school_year') === $schoolYear)>{{ $schoolYear }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-4 flex gap-3">
            <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                Generate Comments
            </button>
            @if($selectedFaculty)
                <a href="{{ route('reports.employee-comments', array_merge(request()->query(), ['print' => 1])) }}" class="inline-flex items-center px-4 py-2.5 bg-slate-700 text-white text-sm font-semibold rounded-lg hover:bg-slate-800 transition-colors">
                    Print / Generate
                </a>
            @endif
        </div>
    </form>
</div>

@if($selectedFaculty)
    <div class="report-shell">
    <div class="mb-5 bg-white border border-gray-200 rounded-2xl shadow-sm p-3 report-header-card">
        <img src="{{ asset('images/report-header.png') }}" alt="Institution Report Header" class="w-full h-auto rounded-lg">
    </div>

    <div class="mb-5">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 report-employee-card">
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $selectedFaculty->user?->name }}</h2>
                    <p class="text-sm text-gray-500">
                        {{ $selectedFaculty->department?->name ?? 'No Department' }}
                        @if($selectedSemester || $selectedSchoolYear)
                            &middot; Scope:
                            {{ $selectedSemester ?: 'All semesters' }},
                            {{ $selectedSchoolYear ?: 'All school years' }}
                        @else
                            &middot; Scope: All terms
                        @endif
                    </p>
                </div>
                <div class="text-sm text-gray-600">
                    <div><span class="font-semibold">Total Comments:</span> {{ $comments->count() }}</div>
                    <div class="print-hide"><span class="font-semibold">Classifications:</span> {{ $commentsByLevel->count() }} levels</div>
                    <div>
                        <span class="font-semibold">Sentiment:</span>
                        Positive {{ $sentimentCounts->get('positive', 0) }},
                        Neutral {{ $sentimentCounts->get('neutral', 0) }},
                        Negative {{ $sentimentCounts->get('negative', 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 print-hide">
        @forelse($sourceCounts as $source => $count)
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $source }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $count }}</div>
            </div>
        @empty
            <div class="md:col-span-3 bg-white border border-gray-200 rounded-2xl shadow-sm p-5 text-gray-500">
                No comments found for this employee and filter set.
            </div>
        @endforelse
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 print-hide">
        <div class="bg-white border border-green-200 rounded-2xl shadow-sm p-4">
            <div class="text-xs font-medium text-green-700 uppercase tracking-wide">Positive</div>
            <div class="mt-1 text-2xl font-bold text-green-800">{{ $sentimentCounts->get('positive', 0) }}</div>
        </div>
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-4">
            <div class="text-xs font-medium text-slate-700 uppercase tracking-wide">Neutral</div>
            <div class="mt-1 text-2xl font-bold text-slate-800">{{ $sentimentCounts->get('neutral', 0) }}</div>
        </div>
        <div class="bg-white border border-red-200 rounded-2xl shadow-sm p-4">
            <div class="text-xs font-medium text-red-700 uppercase tracking-wide">Negative</div>
            <div class="mt-1 text-2xl font-bold text-red-800">{{ $sentimentCounts->get('negative', 0) }}</div>
        </div>
    </div>

    @foreach($commentsByLevel as $level => $items)
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-5 comment-level-card">
            <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
                <div class="text-base font-semibold text-gray-900">{{ $level }}</div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $items->count() }} comments</span>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($items as $comment)
                    <div class="px-5 py-4 comment-item">
                        <div class="flex items-center justify-between gap-3 flex-wrap mb-2 comment-meta">
                            <div class="text-sm text-gray-700">
                                <span class="font-semibold">{{ $comment['source'] }}</span>
                                &middot; Evaluator: {{ $comment['evaluator'] }}
                            </div>
                            <div class="text-xs text-gray-500 comment-meta-right">
                                {{ $comment['semester'] ?? '—' }} / {{ $comment['school_year'] ?? '—' }}
                                @if($comment['score'] !== null)
                                    &middot; Avg: {{ number_format($comment['score'], 2) }}
                                @endif
                                @php
                                    $sentimentLabel = $comment['sentiment'] ?? 'neutral';
                                    $sentimentClass = match ($sentimentLabel) {
                                        'positive' => 'bg-green-100 text-green-800',
                                        'negative' => 'bg-red-100 text-red-800',
                                        default => 'bg-slate-100 text-slate-700',
                                    };
                                @endphp
                                <span class="print-hide">
                                    &middot;
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold {{ $sentimentClass }}">
                                        {{ ucfirst($sentimentLabel) }}
                                    </span>
                                </span>
                                <span class="print-only">
                                    &middot; Sentiment: {{ ucfirst($sentimentLabel) }}
                                </span>
                            </div>
                        </div>
                        <p class="text-sm leading-relaxed text-gray-800 whitespace-pre-line">{{ $comment['comment'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
    </div>
@endif
@endsection

@push('scripts')
@if($printMode && $selectedFaculty)
<script>
    window.addEventListener('load', () => window.print());
</script>
@endif
@endpush
