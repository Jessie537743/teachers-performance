@extends('layouts.app')

@section('title', 'Department Performance Report')
@section('page-title', 'Department Performance Report')

@push('styles')
<style>
    .dept-report-doc {
        --hr-ink: #0f172a;
        --hr-muted: #475569;
        --hr-border: #e2e8f0;
        --hr-accent: #1e3a5f;
        --hr-accent-soft: #f1f5f9;
        max-width: 210mm;
        margin-left: auto;
        margin-right: auto;
        color: var(--hr-ink);
    }
    .dept-stat-grid {
        display: grid;
        gap: 0.75rem;
        grid-template-columns: 1fr;
    }
    @media (min-width: 640px) {
        .dept-stat-grid { grid-template-columns: repeat(3, 1fr); }
    }
    .dept-stat-card {
        border: 1px solid var(--hr-border);
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        padding: 1rem 1.1rem;
        border-left: 3px solid var(--hr-accent);
    }
    .dept-stat-card .dept-stat-label {
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--hr-muted);
    }
    .dept-stat-card .dept-stat-value {
        font-size: 1.375rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--hr-ink);
        margin-top: 0.25rem;
    }
    .dept-table-wrap {
        border: 1px solid var(--hr-border);
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .dept-table-wrap table { border-collapse: collapse; width: 100%; }
    .dept-table-wrap thead th {
        background: linear-gradient(180deg, #1e3a5f 0%, #152a45 100%);
        color: #f8fafc;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 0.65rem 0.65rem;
        text-align: left;
    }
    .dept-table-wrap thead th.dept-th-center { text-align: center; }
    .dept-table-wrap tbody td {
        padding: 0.55rem 0.65rem;
        font-size: 0.8125rem;
        line-height: 1.4;
        border-top: 1px solid var(--hr-border);
        vertical-align: middle;
    }
    .dept-table-wrap tbody tr:nth-child(even) td { background: #fafbfc; }
    .ie-cert-block {
        margin-top: 2.5rem;
        padding-top: 1.75rem;
        border-top: 1px solid var(--hr-border);
        max-width: 22rem;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
    }
    .ie-cert-block .ie-cert-line {
        height: 0;
        border: 0;
        border-bottom: 2px solid var(--hr-ink);
        max-width: 16rem;
        margin: 2rem auto 0.35rem;
    }
    .ie-doc-footer {
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid var(--hr-border);
        font-size: 0.6875rem;
        line-height: 1.5;
        color: var(--hr-muted);
        text-align: center;
    }

    @media print {
        .no-print { display: none !important; }
        aside, .sidebar-overlay-bg, #formLoadingOverlay, main > header { display: none !important; }
        main { margin-left: 0 !important; }
        main > section { padding: 0 !important; margin: 0 !important; }
        body { background: white !important; }
        .dept-report-doc, .dept-report-doc * { color: #0f172a !important; }
        .dept-report-doc .dept-table-wrap thead th {
            background: #1e3a5f !important;
            color: #fff !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .dept-report-doc tbody tr:nth-child(even) td { background: #f8fafc !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .dept-report-doc .dept-stat-card {
            background: #f1f5f9 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .report-shell, .report-shell * { box-shadow: none !important; }
        .report-shell .bg-white, .report-shell [class*="bg-white"] { background: #fff !important; }
        .report-header-card { border: 0 !important; padding: 0 !important; margin-bottom: 10px !important; }
        .dept-table tbody tr { page-break-inside: avoid; break-inside: avoid; }
        .ie-cert-block { page-break-inside: avoid; break-inside: avoid; }
        @page { size: A4; margin: 12mm 12mm; }
    }
</style>
@endpush

@section('content')
<div class="mb-6 animate-slide-up no-print">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Department Performance Report</h1>
    <p class="mt-2 text-sm text-slate-600 max-w-3xl leading-relaxed">
        Official HR summary of faculty performance for a single department: weighted GWA from student, dean/supervisor, self, and peer evaluation components (same weighting as system analytics).
    </p>
    <p class="mt-1.5 text-xs text-slate-500">Select a department and optional period, then <strong class="text-slate-700">Generate report</strong>. Use <strong class="text-slate-700">Print / Save as PDF</strong> for filing.</p>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6 no-print">
    <form method="GET" action="{{ route('reports.department') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
        <div class="md:col-span-2">
            <label for="department_id" class="block text-xs font-semibold uppercase tracking-wide text-slate-600 mb-1.5">Department <span class="text-red-600">*</span></label>
            <select
                id="department_id"
                name="department_id"
                class="w-full rounded-lg border-slate-300 focus:border-blue-600 focus:ring-blue-600 text-sm shadow-sm"
            >
                <option value="">Select department</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected((string) old('department_id', request('department_id')) === (string) $dept->id)>
                        {{ $dept->name }} @if($dept->code)({{ $dept->code }})@endif
                    </option>
                @endforeach
            </select>
            @error('department_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
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
            @if($selectedDepartment)
                <a href="{{ route('reports.department', array_merge(request()->query(), ['print' => 1])) }}" class="inline-flex items-center px-5 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-lg hover:bg-slate-900 transition-colors shadow-sm">
                    Print / PDF
                </a>
            @endif
        </div>
    </form>
    @if($openPeriod)
        <p class="mt-4 text-xs text-slate-500 border-t border-slate-100 pt-3">Open evaluation period: <span class="font-semibold text-slate-700">{{ $openPeriod->school_year }}</span> · <span class="font-semibold text-slate-700">{{ $openPeriod->semester }}</span></p>
    @endif
</div>

@if($selectedDepartment)
<div class="report-shell dept-report-doc pb-10">
    <div class="mb-5 bg-white border border-slate-200/80 rounded-xl shadow-sm p-2 sm:p-3 report-header-card">
        @include('admin.partials.institution-header')
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-md p-6 sm:p-8 mb-6">
        <div class="text-center border-b border-slate-200 pb-6 mb-6">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 mb-2">Human resource · Department performance</p>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 leading-snug tracking-tight px-2">Department Faculty Performance Summary</h2>
            <p class="mt-3 text-sm text-slate-600">
                <span class="font-semibold text-slate-800">{{ $selectedDepartment->name }}</span>
                @if($selectedDepartment->code)<span class="text-slate-500"> ({{ $selectedDepartment->code }})</span>@endif
            </p>
            <p class="mt-2 inline-flex flex-wrap items-center justify-center gap-x-2 text-sm text-slate-600">
                @if($semester || $schoolYear)
                    <span>{{ $schoolYear ?: '—' }}@if($semester)<span class="text-slate-400 mx-1">·</span>{{ $semester }}@endif</span>
                @else
                    <span class="italic text-slate-500">All periods (aggregated)</span>
                @endif
            </p>
        </div>

        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-5 sm:p-6 mb-8">
            <div class="grid gap-3 sm:grid-cols-2 text-sm">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Department</span>
                    <p class="font-semibold text-slate-900 mt-0.5">{{ $selectedDepartment->name }}</p>
                </div>
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Reporting period</span>
                    <p class="font-semibold text-slate-900 mt-0.5">
                        @if($semester || $schoolYear)
                            {{ $schoolYear ?: '—' }}@if($semester) · {{ $semester }}@endif
                        @else
                            All periods (aggregated)
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="dept-stat-grid mb-8">
            <div class="dept-stat-card">
                <div class="dept-stat-label">Faculty in department</div>
                <div class="dept-stat-value">{{ $facultyRows->count() }}</div>
            </div>
            <div class="dept-stat-card">
                <div class="dept-stat-label">Department average GWA</div>
                <div class="dept-stat-value">{{ $departmentAvg !== null ? number_format($departmentAvg, 2) : '—' }}</div>
            </div>
            <div class="dept-stat-card">
                <div class="dept-stat-label">Rated excellent</div>
                <div class="dept-stat-value">{{ $excellentCount }}</div>
            </div>
        </div>

        @if(!$hasData)
            <p class="text-center text-slate-600 py-12 px-4 rounded-lg border border-dashed border-slate-200 bg-slate-50/50 text-sm leading-relaxed max-w-lg mx-auto">
                No active faculty assigned to this department, or no evaluation data is available for the selected school year and semester.
            </p>
        @else
            <p class="text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-3">Faculty detail</p>
            <div class="dept-table-wrap dept-table">
                <table>
                    <thead>
                        <tr>
                            <th class="w-10">#</th>
                            <th>Name</th>
                            <th class="dept-th-center w-[9%]">Student</th>
                            <th class="dept-th-center w-[9%]">Dean</th>
                            <th class="dept-th-center w-[9%]">Self</th>
                            <th class="dept-th-center w-[9%]">Peer</th>
                            <th class="dept-th-center w-[11%]">GWA</th>
                            <th class="w-[14%]">Level</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($facultyRows as $row)
                            <tr>
                                <td class="tabular-nums text-slate-600">{{ $loop->iteration }}</td>
                                <td class="font-medium text-slate-900">{{ $row['user']->name }}</td>
                                <td class="text-center tabular-nums">{{ $row['components']['student'] !== null ? number_format($row['components']['student'], 2) : '—' }}</td>
                                <td class="text-center tabular-nums">{{ $row['components']['dean'] !== null ? number_format($row['components']['dean'], 2) : '—' }}</td>
                                <td class="text-center tabular-nums">{{ $row['components']['self'] !== null ? number_format($row['components']['self'], 2) : '—' }}</td>
                                <td class="text-center tabular-nums">{{ $row['components']['peer'] !== null ? number_format($row['components']['peer'], 2) : '—' }}</td>
                                <td class="text-center font-bold tabular-nums text-slate-900">{{ $row['weighted_average'] !== null ? number_format($row['weighted_average'], 2) : '—' }}</td>
                                <td>
                                    @if($row['performance_level'])
                                        <span class="text-xs font-semibold text-slate-800">{{ $row['performance_level'] }}</span>
                                    @else
                                        <span class="text-xs text-slate-500">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @include('admin.partials.hr-certification', ['wrapClass' => 'ie-cert-block', 'lineClass' => 'ie-cert-line', 'showHeading' => false])

        <div class="ie-doc-footer">
            System-generated report for HR use. Department aggregates from active faculty evaluation records; individual evaluator identities are not listed in this summary.
        </div>
    </div>
</div>

@if($printMode && $selectedDepartment)
    @push('scripts')
    <script>
        window.addEventListener('load', function () { window.print(); });
    </script>
    @endpush
@endif
@endif
@endsection
