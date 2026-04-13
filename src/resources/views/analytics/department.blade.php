@extends('layouts.app')

@section('title', 'Department Analytics')
@section('page-title', 'Department Analytics')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Department Analytics</h1>
        <p class="text-sm text-gray-500 mt-1">Predictive analytics for employee performance — department overview and ML predictions for the selected term.</p>
    </div>
</div>

{{-- Filter --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-5">
    <div class="p-5">
        <form method="GET" action="{{ route('analytics.index') }}" class="flex gap-3.5 items-end flex-wrap">
            <div class="m-0 min-w-[160px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="filter_sy">School Year</label>
                <input type="text" name="school_year" id="filter_sy" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. 2024-2025" value="{{ $schoolYear }}">
            </div>
            <div class="m-0 min-w-[160px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="filter_sem">Semester</label>
                <select name="semester" id="filter_sem" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">All Semesters</option>
                    <option value="1st" {{ $semester === '1st' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd" {{ $semester === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                    <option value="Summer" {{ $semester === 'Summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </div>
            <div class="m-0 min-w-[220px] flex-1 max-w-sm">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="faculty_name_dept">Faculty name (table search)</label>
                <input type="text" name="faculty_name" id="faculty_name_dept" autocomplete="off"
                       class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="Filter performance table…"
                       value="{{ $facultyNameSearch ?? '' }}">
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Apply Filter</button>
            <a href="{{ route('analytics.index') }}" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Reset</a>
        </form>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-3 gap-3.5 mb-5">
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-5 transition hover:shadow-md">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Faculty in Department</div>
        <div class="text-2xl font-bold text-slate-900">{{ $allFacultyRows->count() }}</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-5 transition hover:shadow-md">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Department Average GWA</div>
        <div class="text-2xl font-bold text-slate-900">{{ $departmentAvg !== null ? number_format($departmentAvg, 2) : '—' }}</div>
        <div class="text-xs text-gray-400 mt-1">Among faculty with evaluation data for the period</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-5 transition hover:shadow-md">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Top-tier performers</div>
        <div class="text-2xl font-bold text-slate-900">{{ $departmentTopTierCount }}</div>
        <div class="text-xs text-gray-400 mt-1">Excellent or Outstanding (with data)</div>
    </div>
</div>

{{-- Chart --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
        <span class="font-bold text-slate-900">Performance Distribution</span>
    </div>
    <div class="p-5 max-w-[600px]">
        <canvas id="deanDistChart" height="220"></canvas>
    </div>
</div>

{{-- Faculty Detail Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3 flex-wrap">
        <div>
            <span class="font-bold text-slate-900">Faculty Performance Details</span>
            @if(filled($facultyNameSearch ?? null))
                <p class="text-xs text-gray-500 mt-1">Showing names matching <strong class="text-slate-700">“{{ $facultyNameSearch }}”</strong></p>
            @endif
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $facultyRows->total() }} records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Student Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Dean Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Self Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Peer Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Weighted GWA</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Level</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">ML prediction</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Interventions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($facultyRows as $row)
                @php
                    $personnelForIntervention = $row['profile']->evaluationCriteriaPersonnelType();
                    $analyticsPeriodContext = filled($schoolYear) && filled($semester);
                    $showInterventionLink = $analyticsPeriodContext && $row['performance_level']
                        && \App\Services\EvaluationService::qualifiesForPerformanceIntervention($row['performance_level'], $personnelForIntervention);
                    $mlPred = $row['ml_prediction'] ?? null;
                    $mlBadge = match (($mlPred ?? [])['predicted_performance'] ?? '') {
                        'Excellent' => 'bg-green-100 text-green-800',
                        'Very Good' => 'bg-blue-100 text-blue-800',
                        'Good' => 'bg-amber-100 text-amber-800',
                        'Needs Improvement' => 'bg-orange-100 text-orange-800',
                        'At Risk' => 'bg-red-100 text-red-800',
                        default => 'bg-slate-100 text-slate-800',
                    };
                @endphp
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($facultyRows->currentPage() - 1) * $facultyRows->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $row['user']->name }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $row['components']['student'] !== null ? number_format($row['components']['student'], 2) : '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $row['components']['dean'] !== null ? number_format($row['components']['dean'], 2) : '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $row['components']['self'] !== null ? number_format($row['components']['self'], 2) : '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $row['components']['peer'] !== null ? number_format($row['components']['peer'], 2) : '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $row['weighted_average'] !== null ? number_format($row['weighted_average'], 2) : '—' }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if($row['performance_level'])
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $row['badge_class'] }}">{{ $row['performance_level'] }}</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if(!($mlPeriodReady ?? false))
                            <span class="text-gray-400 text-xs">Set school year &amp; semester</span>
                        @elseif($mlPred === null)
                            <span class="text-gray-400 text-xs" title="No aggregated self/dean/peer feedback for this term">No ML features</span>
                        @elseif(isset($mlPred['error']))
                            <span class="text-rose-600 text-xs font-medium" title="{{ $mlPred['error'] }}">ML unavailable</span>
                        @else
                            <div class="flex flex-col gap-0.5">
                                <span class="inline-flex items-center w-fit px-2.5 py-1 rounded-full text-xs font-bold {{ $mlBadge }}">{{ $mlPred['predicted_performance'] ?? '—' }}</span>
                                @if(isset($mlPred['confidence']))
                                    <span class="text-[11px] text-gray-500">Conf. {{ number_format((float) $mlPred['confidence'] * 100, 1) }}%</span>
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if($showInterventionLink)
                            <a href="{{ route('faculty.intervention-suggestions', ['faculty_profile' => $row['profile']]) }}?school_year={{ urlencode($schoolYear) }}&semester={{ urlencode($semester) }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-100 text-rose-900 hover:bg-rose-200 transition">View plan</a>
                        @else
                            <span class="text-gray-300 text-xs">{{ $analyticsPeriodContext ? '—' : 'Set SY & sem.' }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="11" class="text-center text-gray-500 py-8 px-4">
                        No data available for the selected filters.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($facultyRows->hasPages())
    <div class="px-5 py-4">
        {{ $facultyRows->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const ctx = document.getElementById('deanDistChart');
    if (!ctx) return;

    const labels = @json($chartLabels);
    const data   = @json($chartData);
    const colors = ['#22c55e','#3b82f6','#eab308','#f97316','#ef4444'];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Faculty Count',
                data,
                backgroundColor: colors,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f4f8' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
@endpush
