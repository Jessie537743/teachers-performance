@extends('layouts.app')

@section('title', 'Individual Evaluation Reports')
@section('page-title', 'Individual Evaluation Reports')

@push('styles')
<style>
    /* Screen + print: HR evaluation report document */
    .ie-report-doc {
        --ie-ink: #0f172a;
        --ie-muted: #475569;
        --ie-border: #e2e8f0;
        --ie-accent: #1e3a5f;
        --ie-accent-soft: #f1f5f9;
        max-width: 210mm;
        margin-left: auto;
        margin-right: auto;
        color: var(--ie-ink);
    }
    .ie-report-doc .ie-doc-title {
        font-family: ui-sans-serif, system-ui, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        letter-spacing: -0.02em;
    }
    .ie-meta-grid {
        display: grid;
        gap: 0.75rem 1.5rem;
    }
    @media (min-width: 640px) {
        .ie-meta-grid { grid-template-columns: 9.5rem 1fr; }
    }
    .ie-meta-label {
        font-size: 0.6875rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: var(--ie-muted);
        padding-top: 0.125rem;
    }
    .ie-meta-value {
        font-size: 0.9375rem;
        font-weight: 600;
        color: var(--ie-ink);
        line-height: 1.45;
    }
    .ie-legend-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem 0.75rem;
    }
    .ie-legend-pill {
        display: inline-flex;
        align-items: baseline;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        border-radius: 0.375rem;
        border: 1px solid var(--ie-border);
        background: var(--ie-accent-soft);
        font-size: 0.8125rem;
        line-height: 1.35;
    }
    .ie-legend-pill span.num {
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--ie-accent);
        min-width: 0.65rem;
    }
    .ie-section-title {
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--ie-accent);
        border-bottom: 2px solid var(--ie-accent);
        padding-bottom: 0.35rem;
        margin-bottom: 0.75rem;
    }
    .ie-table-wrap {
        border: 1px solid var(--ie-border);
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .ie-table-wrap table { border-collapse: collapse; width: 100%; }
    .ie-table-wrap thead th {
        background: linear-gradient(180deg, #1e3a5f 0%, #152a45 100%);
        color: #f8fafc;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.65rem 0.75rem;
        text-align: left;
    }
    .ie-table-wrap thead th.ie-th-num,
    .ie-table-wrap thead th.ie-th-desc { text-align: center; }
    .ie-table-wrap tbody td {
        padding: 0.6rem 0.75rem;
        font-size: 0.8125rem;
        line-height: 1.45;
        vertical-align: top;
        border-top: 1px solid var(--ie-border);
    }
    .ie-table-wrap tbody tr:nth-child(even) td { background: #fafbfc; }
    .ie-table-wrap tbody tr.ie-total td {
        background: #f1f5f9 !important;
        font-weight: 700;
        border-top: 2px solid #94a3b8;
    }
    .ie-overall-card {
        border: 1px solid var(--ie-border);
        border-left: 4px solid var(--ie-accent);
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        overflow: hidden;
    }
    .ie-overall-card table { border-collapse: collapse; width: 100%; }
    .ie-overall-card td {
        padding: 0.85rem 1rem;
        font-size: 0.875rem;
        font-weight: 800;
        letter-spacing: 0.04em;
        text-transform: uppercase;
    }
    .ie-overall-card td.ie-oa-val { text-align: center; font-variant-numeric: tabular-nums; }
    .ie-doc-footer {
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid var(--ie-border);
        font-size: 0.6875rem;
        line-height: 1.5;
        color: var(--ie-muted);
        text-align: center;
    }
    .ie-cert-block {
        margin-top: 2.5rem;
        padding-top: 1.75rem;
        border-top: 1px solid var(--ie-border);
        max-width: 22rem;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
    }
    .ie-cert-block .ie-cert-line {
        height: 0;
        border: 0;
        border-bottom: 2px solid var(--ie-ink);
        max-width: 16rem;
        margin: 2rem auto 0.35rem;
    }

    @media print {
        .no-print { display: none !important; }
        aside, .sidebar-overlay-bg, #formLoadingOverlay, main > header { display: none !important; }
        main { margin-left: 0 !important; }
        main > section { padding: 0 !important; margin: 0 !important; }
        body { background: white !important; }
        .ie-report-doc, .ie-report-doc * { color: #0f172a !important; }
        .ie-report-doc .ie-table-wrap thead th {
            background: #1e3a5f !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .ie-report-doc tbody tr:nth-child(even) td { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .ie-report-doc tr.ie-total td { background: #eef2f7 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .ie-report-doc .ie-overall-card {
            background: #f1f5f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .report-shell, .report-shell * { box-shadow: none !important; }
        .report-shell .bg-white, .report-shell [class*="bg-white"] { background: #fff !important; }
        .report-header-card { border: 0 !important; padding: 0 !important; margin-bottom: 10px !important; }
        .item-row, .total-row, .overall-row { page-break-inside: avoid; break-inside: avoid; }
        .ie-section-block { page-break-inside: avoid; break-inside: avoid; }
        @page { size: A4; margin: 12mm 12mm; }
    }
</style>
@endpush

@section('content')
<div class="mb-6 animate-slide-up no-print">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Individual Evaluation Reports</h1>
    <p class="mt-2 text-sm text-slate-600 max-w-3xl leading-relaxed">
        Official HR summary of aggregated ratings. Evaluator names are not shown. Each section lists criteria questions with mean numerical and descriptive ratings, plus category totals.
    </p>
    <p class="mt-1.5 text-xs text-slate-500">Use <strong class="text-slate-700">Generate report</strong> after selecting personnel and report type. <strong class="text-slate-700">Print / Save as PDF</strong> for filing.</p>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6 no-print">
    <form method="GET" action="{{ route('reports.individual-evaluation') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="faculty_profile_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Personnel <span class="text-red-600">*</span></label>
            <select
                id="faculty_profile_id"
                name="faculty_profile_id"
                class="w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-blue-600 text-sm shadow-sm"
            >
                <option value="">Select personnel</option>
                @foreach($facultyOptions as $faculty)
                    <option value="{{ $faculty->id }}" @selected((string) old('faculty_profile_id', request('faculty_profile_id')) === (string) $faculty->id)>
                        {{ $faculty->user?->name ?? '—' }} @if($faculty->department)({{ $faculty->department?->code ?? $faculty->department?->name }})@endif
                    </option>
                @endforeach
            </select>
            @error('faculty_profile_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div class="md:col-span-2">
            <label for="report_type" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Report type <span class="text-red-600">*</span></label>
            <select id="report_type" name="report_type" required class="w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-blue-600 text-sm shadow-sm">
                <option value="">Select type</option>
                <option value="student" @selected(request('report_type') === 'student')>Student evaluation</option>
                <option value="peer" @selected(request('report_type') === 'peer')>Peer evaluation</option>
                <option value="dean" @selected(request('report_type') === 'dean')>Dean / supervisor evaluation</option>
                <option value="self" @selected(request('report_type') === 'self')>Self evaluation</option>
            </select>
        </div>
        <div>
            <label for="semester" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Semester</label>
            <select id="semester" name="semester" class="w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-blue-600 text-sm shadow-sm">
                <option value="">All (aggregated)</option>
                @foreach($periods->pluck('semester')->filter()->unique() as $sem)
                    <option value="{{ $sem }}" @selected(request('semester') === $sem)>{{ $sem }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="school_year" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">School year</label>
            <select id="school_year" name="school_year" class="w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-blue-600 text-sm shadow-sm">
                <option value="">All (aggregated)</option>
                @foreach($periods->pluck('school_year')->filter()->unique() as $sy)
                    <option value="{{ $sy }}" @selected(request('school_year') === $sy)>{{ $sy }}</option>
                @endforeach
            </select>
        </div>
        <div class="md:col-span-6 flex gap-3 flex-wrap items-center pt-1">
            <button type="submit" name="generate" value="1" class="inline-flex items-center px-5 py-2.5 bg-blue-700 text-white text-sm font-semibold rounded-lg hover:bg-blue-800 transition-colors shadow-sm">
                Generate report
            </button>
            @if($selectedFaculty && $reportType && $hasData)
                <a href="{{ route('reports.individual-evaluation', array_merge(request()->query(), ['print' => 1])) }}" class="inline-flex items-center px-5 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-lg hover:bg-slate-900 transition-colors shadow-sm">
                    Print / PDF
                </a>
            @endif
        </div>
    </form>
    @if($openPeriod)
        <p class="mt-4 text-xs text-slate-500 border-t border-slate-100 pt-3">Open evaluation period: <span class="font-semibold text-slate-700">{{ $openPeriod->school_year }}</span> · <span class="font-semibold text-slate-700">{{ $openPeriod->semester }}</span></p>
    @endif
</div>

@if($selectedFaculty && $reportType && $itemized)
<div class="report-shell ie-report-doc pb-10">
    <div class="mb-5 bg-white border border-slate-200/80 rounded-xl shadow-sm p-2 sm:p-3 report-header-card">
        <img src="{{ asset('images/report-header.png') }}" alt="Institution header" class="w-full h-auto rounded-md">
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-md p-6 sm:p-8 mb-6">
        <div class="text-center border-b border-slate-200 pb-6 mb-6">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 mb-2">Human resource · Performance evaluation</p>
            <h2 class="ie-doc-title text-xl sm:text-2xl font-bold text-slate-900 leading-snug px-2">{{ $documentHeading }}</h2>
            <p class="mt-3 inline-flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-sm text-slate-600">
                <span class="font-medium text-slate-800">{{ $reportTitle ?? '' }}</span>
                <span class="text-slate-300 hidden sm:inline">|</span>
                @if($semester || $schoolYear)
                    <span>{{ $schoolYear ?: '—' }}@if($semester)<span class="text-slate-400 mx-1">·</span>{{ $semester }}@endif</span>
                @else
                    <span class="italic text-slate-500">All periods (aggregated)</span>
                @endif
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5 sm:p-6 mb-8">
            <div class="ie-meta-grid">
                <div class="ie-meta-label">Employee name</div>
                <div class="ie-meta-value">{{ $selectedFaculty->user?->name ?? '—' }}</div>
                <div class="ie-meta-label">Department</div>
                <div class="ie-meta-value">{{ $selectedFaculty->department?->name ?? '—' }}</div>
                <div class="ie-meta-label">{{ $itemized['respondent_label'] }}</div>
                <div class="ie-meta-value tabular-nums">{{ $itemized['respondent_count'] }}</div>
            </div>
        </div>

        @if($hasData)
        <div class="mb-8">
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Likert scale (rating equivalent)</p>
            <div class="ie-legend-row">
                @foreach([5, 4, 3, 2, 1] as $rk)
                    @isset($likertLegend[$rk])
                        <span class="ie-legend-pill"><span class="num">{{ $rk }}</span><span>{{ $likertLegend[$rk] }}</span></span>
                    @endisset
                @endforeach
            </div>
        </div>
        @endif

        @if(!$hasData)
            <p class="text-center text-slate-500 py-12 px-4 rounded-lg border border-dashed border-slate-200 bg-slate-50/50 text-sm leading-relaxed">
                No evaluation criteria are configured for this personnel type and evaluator source, or no data is available for the selected filters.
            </p>
        @else
            @foreach($itemized['sections'] as $section)
                <div class="mb-10 ie-section-block">
                    <h3 class="ie-section-title">{{ $section['name'] }}</h3>
                    <div class="ie-table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th class="w-[52%]">Question</th>
                                    <th class="ie-th-num w-[18%]">Numerical</th>
                                    <th class="ie-th-desc w-[30%]">Descriptive</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section['questions'] as $row)
                                    <tr class="item-row">
                                        <td>
                                            <span class="font-bold text-slate-700 tabular-nums">{{ $row['n'] }}.</span>
                                            <span class="text-slate-800">{{ $row['text'] }}</span>
                                        </td>
                                        <td class="text-center font-semibold tabular-nums text-slate-900">{{ $row['numerical'] !== null ? number_format($row['numerical'], 2) : '—' }}</td>
                                        <td class="text-center text-slate-800">{{ $row['descriptive'] }}</td>
                                    </tr>
                                @endforeach
                                <tr class="total-row ie-total">
                                    <td class="uppercase tracking-wide">Category total</td>
                                    <td class="text-center tabular-nums">{{ $section['category_avg'] !== null ? number_format($section['category_avg'], 2) : '—' }}</td>
                                    <td class="text-center">{{ $section['category_descriptive'] }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="overall-row mb-2">
                <div class="ie-overall-card">
                    <table>
                        <tr>
                            <td class="w-[52%]">Overall average</td>
                            <td class="ie-oa-val w-[18%]">{{ $itemized['overall_avg'] !== null ? number_format($itemized['overall_avg'], 2) : '—' }}</td>
                            <td class="ie-oa-val w-[30%] text-slate-800 !font-bold !normal-case !tracking-normal">{{ $itemized['overall_descriptive'] }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @include('admin.partials.hr-certification', ['wrapClass' => 'ie-cert-block', 'lineClass' => 'ie-cert-line', 'showHeading' => false])

            <div class="ie-doc-footer">
                System-generated report for HR use. Aggregated statistics only; individual evaluator identities are not disclosed.
            </div>
        @endif
    </div>
</div>

@if(! $printMode && $selectedFaculty && $reportType && $hasData)
    @plan('ai_predictions')
        <div class="max-w-6xl mx-auto print:hidden">
            @include('admin.partials.ai-improvement-suggestions', ['comments' => $comments ?? ['positive' => collect(), 'negative' => collect()]])
        </div>
    @endplan
@endif

@if($printMode && $selectedFaculty && $reportType && $hasData)
    @push('scripts')
    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
    @endpush
@endif
@endif
@endsection
