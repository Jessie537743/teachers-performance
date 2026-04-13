@extends('layouts.app')

@section('title', 'Sustained Low Performance')
@section('page-title', 'Sustained Low Performance')

@push('styles')
<style>
    .cr-report-doc {
        --cr-ink: #0f172a;
        --cr-muted: #475569;
        --cr-border: #e2e8f0;
        --cr-accent: #1e3a5f;
        max-width: 210mm;
        margin-left: auto;
        margin-right: auto;
        color: var(--cr-ink);
    }
    .cr-legal-panel {
        border-radius: 0.75rem;
        border: 1px solid #fecaca;
        background: linear-gradient(180deg, #fef2f2 0%, #fff 100%);
        padding: 1.25rem 1.35rem;
        margin-bottom: 1.5rem;
    }
    .cr-legal-panel p { font-size: 0.8125rem; line-height: 1.6; color: #450a0a; }
    .cr-table-wrap { border: 1px solid var(--cr-border); border-radius: 0.5rem; overflow: hidden; overflow-x: auto; }
    .cr-table-wrap table { border-collapse: collapse; width: 100%; min-width: 720px; }
    .cr-table-wrap thead th {
        background: linear-gradient(180deg, #1e3a5f 0%, #152a45 100%);
        color: #f8fafc;
        font-size: 0.625rem;
        font-weight: 700;
        letter-spacing: 0.05em;
        text-transform: uppercase;
        padding: 0.65rem 0.5rem;
        text-align: left;
    }
    .cr-table-wrap tbody td {
        padding: 0.55rem 0.5rem;
        font-size: 0.75rem;
        border-top: 1px solid var(--cr-border);
        vertical-align: top;
    }
    .cr-table-wrap tbody tr:nth-child(even) td { background: #fafbfc; }
    .cr-period-cell { font-size: 0.6875rem; line-height: 1.35; }
    .lp-cert-block {
        margin-top: 2.25rem;
        padding-top: 1.75rem;
        border-top: 1px solid var(--cr-border);
        background: transparent;
        max-width: 22rem;
        margin-left: auto;
        margin-right: auto;
        text-align: center;
    }
    .lp-cert-block .lp-cert-line {
        height: 0;
        border: 0;
        border-bottom: 2px solid var(--cr-ink);
        max-width: 16rem;
        margin: 2rem auto 0.35rem;
    }
    @media print {
        .no-print { display: none !important; }
        aside, .sidebar-overlay-bg, #formLoadingOverlay, main > header { display: none !important; }
        main { margin-left: 0 !important; }
        main > section { padding: 0 !important; margin: 0 !important; }
        body { background: white !important; }
        .cr-report-doc * { color: #0f172a !important; }
        .cr-table-wrap thead th { background: #1e3a5f !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .cr-legal-panel { background: #fef2f2 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .report-shell * { box-shadow: none !important; }
        .report-header-card { border: 0 !important; padding: 0 !important; margin-bottom: 10px !important; }
        .lp-cert-block { page-break-inside: avoid; break-inside: avoid; }
        @page { size: A4; margin: 12mm 12mm; }
    }
</style>
@endpush

@section('content')
<div class="mb-6 animate-slide-up no-print">
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Sustained Low Performance</h1>
    <p class="mt-2 text-sm text-slate-600 max-w-3xl leading-relaxed">
        Identifies active faculty who, under the <strong class="text-slate-800">same weighted GWA rules</strong> as analytics, show <strong class="text-slate-800">three consecutive evaluation periods</strong> each in the low band for their personnel type. Periods with <strong class="text-slate-800">no evaluation data</strong> do not count and break a streak.
    </p>
    <ul class="mt-2 text-xs text-slate-600 max-w-3xl list-disc list-inside space-y-1">
        @foreach(\App\Services\EvaluationService::interventionLowBandReportLines() as $line)
            <li><span class="font-semibold text-slate-700">{{ $line['category'] }}:</span> {{ $line['levels'] }}</li>
        @endforeach
    </ul>
    <p class="mt-2 text-xs text-slate-500 max-w-3xl">
        Evaluation periods are ordered by the <strong class="text-slate-700">start date</strong> recorded for each period. Optional: restrict to one department. Click <strong class="text-slate-700">Generate report</strong>, then <strong class="text-slate-700">Print / PDF</strong> if needed.
    </p>
</div>

<div class="bg-white border border-slate-200 rounded-2xl shadow-sm p-5 mb-6 no-print">
    <form method="GET" action="{{ route('reports.chronic-low-performance') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
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
        <div class="md:col-span-6 flex gap-3 flex-wrap items-center pt-1">
            <button type="submit" name="generate" value="1" class="inline-flex items-center px-5 py-2.5 bg-rose-800 text-white text-sm font-semibold rounded-lg hover:bg-rose-900 transition-colors shadow-sm">
                Generate report
            </button>
            @if($shouldLoad)
                <a href="{{ route('reports.chronic-low-performance', array_merge(request()->query(), ['print' => 1])) }}" class="inline-flex items-center px-5 py-2.5 bg-slate-800 text-white text-sm font-semibold rounded-lg hover:bg-slate-900 transition-colors shadow-sm">
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
<div class="report-shell cr-report-doc pb-10">
    <div class="mb-5 bg-white border border-slate-200/80 rounded-xl shadow-sm p-2 sm:p-3 report-header-card">
        <img src="{{ asset('images/report-header.png') }}" alt="Institution header" class="w-full h-auto rounded-md">
    </div>

    <div class="bg-white border border-slate-200 rounded-xl shadow-md p-6 sm:p-8 mb-6">
        <div class="text-center border-b border-slate-200 pb-5 mb-5">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500 mb-2">Human resource · Compliance review</p>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-900 leading-snug tracking-tight px-2">Sustained Low Performance — three consecutive periods (low band)</h2>
            <p class="mt-2 text-sm text-slate-600">
                @if($selectedDepartment)
                    Scope: <span class="font-semibold text-slate-800">{{ $selectedDepartment->name }}</span>
                @else
                    Scope: <span class="font-medium text-slate-800">All departments</span>
                @endif
            </p>
            <p class="mt-1 text-xs text-slate-500">Chronological periods in system: {{ $orderedPeriodCount }} &middot; Match uses the <strong>most recent</strong> qualifying three-period streak per employee.</p>
        </div>

        <div class="cr-legal-panel">
            <p class="font-bold text-red-950 mb-2">Personnel manual and legal process</p>
            <p>
                This report is an <strong>administrative identification tool</strong> only. Low-band rules follow the same tiers as intervention planning: <strong>non-teaching</strong> &mdash; Below Average or Poor; <strong>Dean, Head, or Administrator</strong> &mdash; Below Average or Unsatisfactory; <strong>teaching</strong> &mdash; Fair or Poor. Any sanction, separation, or measure with <strong>legal implication</strong> must follow the <strong>official Personnel Manual</strong> on file with Human Resources, applicable law, and due process. HR shall verify complete records, documentation, and advice of counsel before formal action.
            </p>
        </div>

        @if(!$hasData)
            <p class="text-center text-slate-600 py-12 px-4 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 text-sm leading-relaxed max-w-lg mx-auto">
                @if($orderedPeriodCount < 3)
                    At least three evaluation periods (with start dates) are required in the system to detect a three-period streak. Currently: {{ $orderedPeriodCount }} distinct period(s).
                @else
                    No active faculty meet the Sustained Low Performance criteria (three consecutive low-band periods) under the selected scope, or evaluation data is insufficient across consecutive periods.
                @endif
            </p>
        @else
            <div class="cr-table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="w-10">#</th>
                            <th class="min-w-[120px]">Name</th>
                            <th class="min-w-[100px]">Department</th>
                            <th>Period 1 (oldest of streak)</th>
                            <th>Period 2</th>
                            <th>Period 3 (newest)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($streakRows as $idx => $row)
                            <tr>
                                <td class="tabular-nums text-slate-500">{{ $idx + 1 }}</td>
                                <td class="font-semibold text-slate-900">{{ $row['user']->name }}</td>
                                <td class="text-slate-700">{{ $row['department'] }}</td>
                                @foreach($row['streak'] as $p)
                                    <td class="cr-period-cell">
                                        <div class="font-medium text-slate-800">{{ $p['school_year'] }} · {{ $p['semester'] }}</div>
                                        <div class="tabular-nums">GWA {{ number_format($p['weighted_average'], 2) }}</div>
                                        <div>
                                            <span class="inline-flex px-1.5 py-0.5 rounded text-[10px] font-bold {{ $p['badge_class'] }}">{{ $p['performance_level'] }}</span>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="lp-cert-block">
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
