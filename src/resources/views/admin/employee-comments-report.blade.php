@extends('layouts.app')

@section('title', 'Evaluation comments')
@section('page-title', 'Evaluation comments')

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

    .sticky-head th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: #f8fafc;
    }
</style>
@endpush

@section('content')
<div class="mb-5 animate-slide-up no-print">
    <h1 class="text-2xl font-bold text-gray-900">Evaluation comments &amp; classification</h1>
    <p class="mt-1 text-sm text-gray-500">Generate qualitative feedback (from student, dean, self, and peer evaluations) for selected personnel, classified by performance level and sentiment (positive, negative, neutral).</p>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 mb-5 no-print">
    <form method="GET" action="{{ route('reports.employee-comments') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="faculty_profile_id" class="block text-sm font-medium text-gray-700 mb-1">Personnel</label>
            <select id="faculty_profile_id" name="faculty_profile_id" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">Select personnel</option>
                @foreach($facultyOptions as $faculty)
                    <option value="{{ $faculty->id }}" @selected((string) request('faculty_profile_id') === (string) $faculty->id)>
                        {{ $faculty->user?->name ?? 'Faculty' }} ({{ $faculty->department?->code ?? 'No Dept' }})
                    </option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="employee_name" class="block text-sm font-medium text-gray-700 mb-1">Search personnel name</label>
            <input
                id="employee_name"
                name="employee_name"
                type="text"
                value="{{ $selectedEmployeeName }}"
                placeholder="Type name…"
                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
            >
        </div>
        <div>
            <label for="semester" class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
            <select id="semester" name="semester" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm" title="{{ $selectedSchoolYear ? 'Only semesters defined for the selected school year are listed.' : 'Choose a school year to narrow semesters to that year.' }}">
                <option value="">All semesters</option>
                @foreach($semesterOptions as $sem)
                    <option value="{{ $sem }}" @selected((string) ($selectedSemester ?? '') === (string) $sem)>{{ $sem }}</option>
                @endforeach
            </select>
            @if($selectedSchoolYear)
                <p class="mt-1 text-xs text-gray-500">Filtered to <span class="font-medium">{{ $selectedSchoolYear }}</span>.</p>
            @endif
        </div>
        <div>
            <label for="department" class="block text-sm font-medium text-gray-700 mb-1">Department</label>
            <select id="department" name="department" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">All departments</option>
                @foreach($departmentOptions as $department)
                    <option value="{{ $department }}" @selected($selectedDepartment === $department)>{{ $department }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="school_year" class="block text-sm font-medium text-gray-700 mb-1">School Year</label>
            <select id="school_year" name="school_year" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="">All school years</option>
                @foreach($schoolYearOptions as $schoolYear)
                    <option value="{{ $schoolYear }}" @selected((string) ($selectedSchoolYear ?? '') === (string) $schoolYear)>{{ $schoolYear }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-2">
            <label for="comment_search" class="block text-sm font-medium text-gray-700 mb-1">Search Comments</label>
            <input
                id="comment_search"
                name="comment_search"
                type="text"
                value="{{ $commentSearch }}"
                placeholder="Search by comment text, evaluator, or category"
                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500 text-sm"
            >
        </div>
        <div class="md:col-span-6 flex gap-3 flex-wrap">
            <button type="submit" name="generate_all" value="0" class="inline-flex items-center px-4 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">
                Generate evaluation comments
            </button>
            <button type="submit" name="generate_all" value="1" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 transition-colors">
                Generate all by personnel name
            </button>
            @if($selectedFaculty)
                <a href="{{ route('reports.employee-comments', array_merge(request()->query(), ['print' => 1])) }}" class="inline-flex items-center px-4 py-2.5 bg-slate-700 text-white text-sm font-semibold rounded-lg hover:bg-slate-800 transition-colors">
                    Print
                </a>
                <a href="{{ route('reports.employee-comments', request()->query()) }}" onclick="window.print(); return false;" class="inline-flex items-center px-4 py-2.5 bg-emerald-700 text-white text-sm font-semibold rounded-lg hover:bg-emerald-800 transition-colors">
                    Export PDF
                </a>
            @elseif($generateAll && $allFacultyReports->isNotEmpty())
                <a href="{{ route('reports.employee-comments', array_merge(request()->query(), ['print' => 1, 'generate_all' => 1])) }}" class="inline-flex items-center px-4 py-2.5 bg-slate-700 text-white text-sm font-semibold rounded-lg hover:bg-slate-800 transition-colors">
                    Print All
                </a>
            @endif
        </div>
    </form>
</div>

@if($selectedFaculty)
    <div class="report-shell">
    <div class="mb-5 bg-white border border-gray-200 rounded-2xl shadow-sm p-3 report-header-card">
        @include('admin.partials.institution-header')
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
                    <div><span class="font-semibold">Total evaluation comments:</span> {{ $comments->count() }}</div>
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
                No comments found for this personnel member and filter set.
            </div>
        @endforelse
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5 print-hide">
        <div class="bg-white border border-green-200 rounded-2xl shadow-sm p-4">
            <div class="text-xs font-medium text-green-700 uppercase tracking-wide">Positive</div>
            <div class="mt-1 text-2xl font-bold text-green-800">{{ $sentimentCounts->get('positive', 0) }}</div>
        </div>
        <div class="bg-white border border-yellow-200 rounded-2xl shadow-sm p-4">
            <div class="text-xs font-medium text-yellow-700 uppercase tracking-wide">Neutral</div>
            <div class="mt-1 text-2xl font-bold text-yellow-800">{{ $sentimentCounts->get('neutral', 0) }}</div>
        </div>
        <div class="bg-white border border-red-200 rounded-2xl shadow-sm p-4">
            <div class="text-xs font-medium text-red-700 uppercase tracking-wide">Negative</div>
            <div class="mt-1 text-2xl font-bold text-red-800">{{ $sentimentCounts->get('negative', 0) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 mb-5 print-hide">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 lg:col-span-1">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Sentiment Distribution</h3>
            <canvas id="sentimentPieChart" height="220"></canvas>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 lg:col-span-2">
            <h3 class="text-sm font-semibold text-gray-900 mb-3">Comments per Category</h3>
            <canvas id="categoryBarChart" height="220"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5 print-hide">
        <div class="bg-white border border-emerald-200 rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-emerald-900 mb-3">Top Strengths</h3>
            <ul class="space-y-2 text-sm text-gray-700">
                @forelse(($insights['strengths'] ?? []) as $strength)
                    <li class="flex items-start gap-2"><span class="text-emerald-600">-</span><span>{{ $strength }}</span></li>
                @empty
                    <li class="text-gray-500">No clear strengths detected from current filtered comments.</li>
                @endforelse
            </ul>
        </div>
        <div class="bg-white border border-rose-200 rounded-2xl shadow-sm p-5">
            <h3 class="text-sm font-semibold text-rose-900 mb-3">Top Issues</h3>
            <ul class="space-y-2 text-sm text-gray-700">
                @forelse(($insights['issues'] ?? []) as $issue)
                    <li class="flex items-start gap-2"><span class="text-rose-600">-</span><span>{{ $issue }}</span></li>
                @empty
                    <li class="text-gray-500">No clear issues detected from current filtered comments.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-5 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <div class="text-base font-semibold text-gray-900">Detailed Comments</div>
            <span class="text-xs font-medium text-gray-500">{{ $comments->count() }} total records</span>
        </div>
        <div class="overflow-x-auto max-h-[540px]">
            <table class="min-w-full text-sm">
                <thead class="sticky-head">
                    <tr class="text-left text-gray-600 border-b border-gray-200">
                        <th class="px-4 py-3 font-semibold">Personnel</th>
                        <th class="px-4 py-3 font-semibold">Category</th>
                        <th class="px-4 py-3 font-semibold">Evaluator</th>
                        <th class="px-4 py-3 font-semibold">Semester / SY</th>
                        <th class="px-4 py-3 font-semibold">Sentiment</th>
                        <th class="px-4 py-3 font-semibold">Comment</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($commentsPage?->items() ?? [] as $comment)
                        @php
                            $sentimentLabel = $comment['sentiment'] ?? 'neutral';
                            $sentimentClass = match ($sentimentLabel) {
                                'positive' => 'bg-green-100 text-green-800',
                                'negative' => 'bg-red-100 text-red-800',
                                default => 'bg-yellow-100 text-yellow-800',
                            };
                        @endphp
                        <tr class="align-top">
                            <td class="px-4 py-3 text-gray-800">{{ $selectedFaculty->user?->name }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $comment['source'] }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $comment['evaluator'] }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $comment['semester'] ?? '—' }} / {{ $comment['school_year'] ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $sentimentClass }}">
                                    {{ ucfirst($sentimentLabel) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-800 whitespace-pre-line">{{ $comment['comment'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-4 text-gray-500">No comments found for the selected filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($commentsPage)
            <div class="px-5 py-3 border-t border-gray-200 bg-gray-50 print-hide">
                {{ $commentsPage->links() }}
            </div>
        @endif
    </div>
    </div>
@elseif($generateAll)
    <div class="report-shell">
    <div class="mb-5 bg-white border border-gray-200 rounded-2xl shadow-sm p-3 report-header-card">
        @include('admin.partials.institution-header')
    </div>

    @if($allFacultyReports->isEmpty())
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 text-gray-500">
            No evaluation comments found for the selected name/semester/school-year filters.
        </div>
    @endif

    @foreach($allFacultyReports as $facultyReport)
        @php
            $faculty = $facultyReport['faculty'];
            $facultyComments = $facultyReport['comments'];
            $facultyCommentsByLevel = $facultyReport['commentsByLevel'];
            $facultySentimentCounts = $facultyReport['sentimentCounts'];
        @endphp

        <div class="mb-5">
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 report-employee-card">
                <div class="flex items-center justify-between gap-4 flex-wrap">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ $faculty->user?->name }}</h2>
                        <p class="text-sm text-gray-500">
                            {{ $faculty->department?->name ?? 'No Department' }}
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
                        <div><span class="font-semibold">Total evaluation comments:</span> {{ $facultyComments->count() }}</div>
                        <div class="print-hide"><span class="font-semibold">Classifications:</span> {{ $facultyCommentsByLevel->count() }} levels</div>
                        <div>
                            <span class="font-semibold">Sentiment:</span>
                            Positive {{ $facultySentimentCounts->get('positive', 0) }},
                            Neutral {{ $facultySentimentCounts->get('neutral', 0) }},
                            Negative {{ $facultySentimentCounts->get('negative', 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @foreach($facultyCommentsByLevel as $level => $items)
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
                                        default => 'bg-yellow-100 text-yellow-800',
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
    @endforeach
    </div>
@endif
@endsection

@push('scripts')
@if($printMode && ($selectedFaculty || $generateAll))
<script>
    window.addEventListener('load', () => window.print());
</script>
@endif

@if($selectedFaculty)
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@php
    $sentimentChartData = [
        'positive' => (int) $sentimentCounts->get('positive', 0),
        'neutral' => (int) $sentimentCounts->get('neutral', 0),
        'negative' => (int) $sentimentCounts->get('negative', 0),
    ];
@endphp
<script>
    (function () {
        const sentimentCounts = @json($sentimentChartData);
        const sourceCounts = @json($sourceCounts->toArray());

        const pieEl = document.getElementById('sentimentPieChart');
        if (pieEl) {
            new Chart(pieEl, {
                type: 'pie',
                data: {
                    labels: ['Positive', 'Neutral', 'Negative'],
                    datasets: [{
                        data: [sentimentCounts.positive, sentimentCounts.neutral, sentimentCounts.negative],
                        backgroundColor: ['#22c55e', '#facc15', '#ef4444'],
                        borderWidth: 1,
                    }],
                },
                options: {
                    plugins: {
                        legend: { position: 'bottom' },
                    },
                },
            });
        }

        const barEl = document.getElementById('categoryBarChart');
        if (barEl) {
            new Chart(barEl, {
                type: 'bar',
                data: {
                    labels: Object.keys(sourceCounts),
                    datasets: [{
                        label: 'Evaluation comments',
                        data: Object.values(sourceCounts),
                        backgroundColor: '#3b82f6',
                    }],
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 },
                        },
                    },
                    plugins: {
                        legend: { display: false },
                    },
                },
            });
        }
    })();
</script>
@endif
@endpush
