@extends('layouts.app')

@section('title', 'Low Performance Personnel Report')
@section('page-title', 'Low Performance Personnel Report')

@push('styles')
<style>
    .lp-report-doc {
        --lp-ink: #0f172a;
        --lp-muted: #475569;
        --lp-border: #e2e8f0;
        --lp-accent: #1e3a5f;
        --lp-warn: #b45309;
        --lp-warn-soft: #fffbeb;
        max-width: 210mm;
        margin-left: auto;
        margin-right: auto;
        color: var(--lp-ink);
    }
    .lp-meta-panel {
        border-radius: 0.75rem;
        border: 1px solid var(--lp-border);
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1.25rem 1.35rem;
        margin-bottom: 2rem;
    }
    .lp-meta-grid {
        display: grid;
        gap: 0.85rem 1.5rem;
    }
    @media (min-width: 640px) {
        .lp-meta-grid { grid-template-columns: 9rem 1fr; }
    }
    .lp-meta-label {
        font-size: 0.625rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--lp-muted);
        padding-top: 0.15rem;
    }
    .lp-meta-value {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--lp-ink);
        line-height: 1.45;
    }
    .lp-criteria-box {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px dashed var(--lp-border);
        font-size: 0.6875rem;
        line-height: 1.65;
        color: var(--lp-muted);
    }
    .lp-criteria-box strong { color: #334155; font-weight: 700; }
    .lp-stat-grid { display: grid; gap: 0.75rem; grid-template-columns: 1fr; }
    @media (min-width: 640px) { .lp-stat-grid { grid-template-columns: repeat(3, 1fr); } }
    .lp-stat-card {
        border: 1px solid var(--lp-border);
        border-radius: 0.5rem;
        padding: 1rem 1.15rem;
        background: #fff;
        border-left: 4px solid var(--lp-accent);
    }
    .lp-stat-card.lp-stat-warn { border-left-color: var(--lp-warn); background: linear-gradient(135deg, #fff 0%, var(--lp-warn-soft) 100%); }
    .lp-stat-card .lp-sl {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--lp-muted);
    }
    .lp-stat-card .lp-sv {
        font-size: 1.5rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        margin-top: 0.35rem;
        letter-spacing: -0.02em;
    }
    .lp-section-label {
        font-size: 0.6875rem;
        font-weight: 800;
        letter-spacing: 0.12em;
        text-transform: uppercase;
        color: var(--lp-accent);
        border-bottom: 2px solid var(--lp-accent);
        padding-bottom: 0.35rem;
        margin-bottom: 0.85rem;
        display: inline-block;
    }
    .lp-table-wrap {
        border: 1px solid var(--lp-border);
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .lp-table-wrap table { border-collapse: collapse; width: 100%; }
    .lp-table-wrap thead th {
        background: linear-gradient(180deg, #1e3a5f 0%, #152a45 100%);
        color: #f8fafc;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.65rem 0.6rem;
        text-align: left;
    }
    .lp-table-wrap thead th.lp-c { text-align: center; }
    .lp-table-wrap tbody td {
        padding: 0.6rem 0.6rem;
        font-size: 0.8125rem;
        line-height: 1.4;
        border-top: 1px solid var(--lp-border);
        vertical-align: middle;
    }
    .lp-table-wrap tbody tr:nth-child(even) td { background: #fafbfc; }
    .lp-cert-block {
        margin-top: 2.75rem;
        padding-top: 1.75rem;
        border-top: 1px solid var(--lp-border);
        background: transparent;
        max-width: 22rem;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
    }
    .lp-cert-block .lp-cert-line {
        height: 0;
        border: 0;
        border-bottom: 2px solid var(--lp-ink);
        max-width: 16rem;
        margin: 2rem auto 0.4rem;
    }
    @media print {
        .no-print { display: none !important; }
        aside, .sidebar-overlay-bg, #formLoadingOverlay, main > header { display: none !important; }
        main { margin-left: 0 !important; }
        main > section { padding: 0 !important; margin: 0 !important; }
        body { background: white !important; }
        .lp-report-doc, .lp-report-doc * { color: #0f172a !important; }
        .lp-table-wrap thead th {
            background: #1e3a5f !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .lp-table-wrap tbody tr:nth-child(even) td { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .lp-meta-panel, .lp-stat-card {
            background: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .lp-cert-block {
            background: transparent !important;
        }
        .lp-stat-card.lp-stat-warn { background: #fffbeb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .report-shell * { box-shadow: none !important; }
        .report-header-card { border: 0 !important; padding: 0 !important; margin-bottom: 10px !important; }
        .lp-table-wrap tbody tr { page-break-inside: avoid; break-inside: avoid; }
        .lp-cert-block { page-break-inside: avoid; break-inside: avoid; }
        @page { size: A4; margin: 12mm 12mm; }
    }
</style>
@endpush

@section('content')
<div class="mb-6 animate-slide-up no-print">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Low performance personnel report</h1>
    <p class="mt-2 text-sm text-slate-600 max-w-3xl leading-relaxed">
        This register is generated automatically from the same weighted GWA used in analytics. It includes only personnel whose overall rating falls in the <strong class="text-slate-800">low-performance bands</strong> that trigger HR follow-up (aligned with intervention rules).
    </p>
    <p class="mt-2 text-xs text-slate-500 max-w-3xl leading-relaxed">
        <span class="font-semibold text-slate-600">Who is listed (by personnel type):</span>
    </p>
    <ul class="mt-1.5 text-xs text-slate-600 max-w-3xl list-disc list-inside space-y-1">
        @foreach(\App\Services\EvaluationService::interventionLowBandReportLines() as $line)
            <li><span class="font-semibold text-slate-700">{{ $line['category'] }}:</span> {{ $line['levels'] }}</li>
        @endforeach
    </ul>
    <p class="mt-2 text-xs text-slate-500">Filter by department and/or period, then <strong class="text-slate-700">Generate list</strong>. Leave filters blank to aggregate across all departments and periods. Use <strong class="text-slate-700">Print / Save as PDF</strong> for HR files.</p>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6 no-print">
    <form method="GET" action="{{ route('reports.low-performance-personnel') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="department_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Department</label>
            <select id="department_id" name="department_id" class="w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-blue-600 text-sm shadow-sm">
                <option value="">All departments</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected((string) old('department_id', request('department_id')) === (string) $dept->id)>
                        {{ $dept->name }} @if($dept->code)({{ $dept->code }})@endif
                    </option>
                @endforeach
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
            <button type="submit" name="generate" value="1" class="inline-flex items-center px-5 py-2.5 bg-amber-700 text-white text-sm font-semibold rounded-lg hover:bg-amber-800 transition-colors shadow-sm">
                Generate list
            </button>
            @if($shouldLoad)
                <a href="{{ route('reports.low-performance-personnel', array_merge(request()->query(), ['print' => 1])) }}" class="inline-flex items-center px-5 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-lg hover:bg-slate-900 transition-colors shadow-sm">
                    Print / PDF
                </a>
            @endif
        </div>
    </form>
    @if($openPeriod)
        <p class="mt-4 text-xs text-slate-500 border-t border-slate-100 pt-3">Open evaluation period: <span class="font-semibold text-slate-700">{{ $openPeriod->school_year }}</span> · <span class="font-semibold text-slate-700">{{ $openPeriod->semester }}</span></p>
    @endif
</div>

@if($shouldLoad)
<div class="report-shell lp-report-doc pb-10">
    <div class="mb-5 bg-white border border-slate-200/80 rounded-xl shadow-sm p-2 sm:p-3 report-header-card">
        <img src="{{ asset('images/report-header.png') }}" alt="Institution header" class="w-full h-auto rounded-md">
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-md p-6 sm:p-8 mb-6">
        <div class="text-center border-b border-slate-200 pb-6 mb-6">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 mb-2">Human resource · Performance monitoring</p>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 leading-snug tracking-tight px-2">Low performance personnel register</h2>
            <p class="mt-2 text-sm text-slate-500 max-w-2xl mx-auto">Fair / poor and equivalent bands · Weighted general weighted average (GWA)</p>
        </div>

        <div class="lp-meta-panel">
            <div class="lp-meta-grid">
                <div class="lp-meta-label">Scope</div>
                <div class="lp-meta-value">
                    @if($selectedDepartment)
                        {{ $selectedDepartment->name }}@if($selectedDepartment->code) <span class="text-slate-500 font-medium">({{ $selectedDepartment->code }})</span>@endif
                    @else
                        All active departments
                    @endif
                </div>
                <div class="lp-meta-label">Reporting period</div>
                <div class="lp-meta-value">
                    @if($semester || $schoolYear)
                        {{ $schoolYear ?: '—' }}@if($semester) · {{ $semester }}@endif
                    @else
                        All periods (aggregated)
                    @endif
                </div>
            </div>
            <div class="lp-criteria-box">
                <strong>Listing criteria.</strong>
                A row appears when overall GWA falls in the low band for that employee&rsquo;s type: non-teaching &mdash; <strong>Below Average or Poor</strong>; Dean, Head, or Administrator &mdash; <strong>Below Average or Unsatisfactory</strong>; teaching &mdash; <strong>Fair or Poor</strong>. Sorted weakest level first, then by name.
            </div>
        </div>

        <div class="lp-stat-grid mb-8">
            <div class="lp-stat-card">
                <div class="lp-sl">Faculty in scope</div>
                <div class="lp-sv text-slate-900">{{ $allFacultyCount }}</div>
            </div>
            <div class="lp-stat-card lp-stat-warn">
                <div class="lp-sl">In low band</div>
                <div class="lp-sv text-amber-950">{{ $lowPerformanceRows->count() }}</div>
            </div>
            <div class="lp-stat-card">
                <div class="lp-sl">Share of scope</div>
                <div class="lp-sv text-slate-900">
                    @if($allFacultyCount > 0)
                        {{ number_format(100 * $lowPerformanceRows->count() / $allFacultyCount, 1) }}%
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>

        @if(!$hasData)
            <p class="text-center text-slate-600 py-12 px-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 text-sm leading-relaxed max-w-lg mx-auto">
                No personnel match the low-performance criteria for the selected filters, or there is no faculty data in scope.
            </p>
        @else
            <div class="mb-1">
                <span class="lp-section-label">Personnel detail</span>
            </div>
            <p class="text-xs text-slate-500 mb-3">Weakest performance level first, then alphabetical by employee name.</p>
            <div class="lp-table-wrap overflow-x-auto">
                <table>
                    <thead>
                        <tr>
                            <th class="w-9">#</th>
                            <th class="min-w-[128px]">Name</th>
                            <th class="min-w-[104px]">Department</th>
                            <th class="min-w-[92px]">Type</th>
                            <th class="lp-c w-[8%]">Student</th>
                            <th class="lp-c w-[8%]">Dean</th>
                            <th class="lp-c w-[8%]">Self</th>
                            <th class="lp-c w-[8%]">Peer</th>
                            <th class="lp-c w-[9%]">GWA</th>
                            <th class="min-w-[96px]">Level</th>
                            <th class="min-w-[88px] no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowPerformanceRows as $row)
                            @php
                                $personnelForIntervention = $row['profile']->evaluationCriteriaPersonnelType();
                                $analyticsPeriodContext = filled($schoolYear) && filled($semester);
                                $showInterventionLink = $analyticsPeriodContext && $row['performance_level']
                                    && \App\Services\EvaluationService::qualifiesForPerformanceIntervention($row['performance_level'], $personnelForIntervention);
                            @endphp
                            <tr>
                                <td class="tabular-nums text-slate-500">{{ $loop->iteration }}</td>
                                <td class="font-semibold text-slate-900">{{ $row['user']->name }}</td>
                                <td class="text-slate-700">{{ $row['department'] }}</td>
                                <td class="text-slate-600 text-[11px] leading-snug">{{ $row['profile']->evaluationCriteriaPersonnelTypeLabel() }}</td>
                                <td class="text-center tabular-nums text-slate-800">{{ $row['components']['student'] !== null ? number_format($row['components']['student'], 2) : '—' }}</td>
                                <td class="text-center tabular-nums text-slate-800">{{ $row['components']['dean'] !== null ? number_format($row['components']['dean'], 2) : '—' }}</td>
                                <td class="text-center tabular-nums text-slate-800">{{ $row['components']['self'] !== null ? number_format($row['components']['self'], 2) : '—' }}</td>
                                <td class="text-center tabular-nums text-slate-800">{{ $row['components']['peer'] !== null ? number_format($row['components']['peer'], 2) : '—' }}</td>
                                <td class="text-center font-bold tabular-nums text-slate-900">{{ $row['weighted_average'] !== null ? number_format($row['weighted_average'], 2) : '—' }}</td>
                                <td>
                                    @if($row['performance_level'])
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-bold {{ $row['badge_class'] }}">{{ $row['performance_level'] }}</span>
                                    @else
                                        <span class="text-xs text-slate-500">—</span>
                                    @endif
                                </td>
                                <td class="no-print">
                                    @if($showInterventionLink)
                                        <a href="{{ route('faculty.intervention-suggestions', ['faculty_profile' => $row['profile']]) }}?school_year={{ urlencode($schoolYear) }}&semester={{ urlencode($semester) }}"
                                           class="inline-flex px-2.5 py-1 rounded-lg text-[11px] font-semibold bg-rose-100 text-rose-900 hover:bg-rose-200 transition-colors">Intervention plan</a>
                                    @else
                                        <span class="text-[11px] text-slate-400">{{ $analyticsPeriodContext ? '—' : 'Set SY &amp; sem.' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="lp-cert-block">
            <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-500 mb-4">Certification</p>
            <p class="text-xs font-semibold text-slate-600 mb-3">Certified by:</p>
            <p class="text-sm font-bold text-slate-900 uppercase tracking-wide leading-snug">RICKY A. DESTACAMENTO, MA, RGC</p>
            <p class="text-sm font-semibold text-slate-800 mt-1.5">Head, Human Resource</p>
            <hr class="lp-cert-line" aria-hidden="true">
            <p class="text-[10px] text-slate-500 uppercase tracking-wider">Signature</p>
        </div>
    </div>
</div>

@if($printMode)
    @push('scripts')
    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
    @endpush
@endif
@endif
@endsection
