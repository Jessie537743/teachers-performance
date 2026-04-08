@extends('layouts.app')

@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Analytics</h1>
        <p class="text-sm text-gray-500 mt-1">System-wide performance insights and trends.</p>
    </div>
</div>

{{-- Filter Form --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-5">
    <div class="p-5">
        <form method="GET" action="{{ route('analytics.index') }}" class="flex gap-3.5 items-end flex-wrap" id="analyticsFilterForm">
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
            <div class="m-0 min-w-[280px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="personnel_profile_id">Personnel (Historical Trend)</label>
                <select name="personnel_profile_id" id="personnel_profile_id" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    @forelse($personnelOptions as $option)
                        <option value="{{ $option['profile_id'] }}" {{ (int) $selectedPersonnelProfileId === (int) $option['profile_id'] ? 'selected' : '' }}>
                            {{ $option['name'] }}
                        </option>
                    @empty
                        <option value="">No faculty personnel found</option>
                    @endforelse
                </select>
            </div>
            <a href="{{ route('analytics.index') }}" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Reset</a>
        </form>
    </div>
</div>

{{-- Stats Summary --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 mb-5">
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-5 transition hover:shadow-md">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total Faculty Evaluated</div>
        <div class="text-2xl font-bold text-slate-900">{{ $allFacultyRows->count() }}</div>
        <div class="text-xs text-gray-400 mt-1">With performance data</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-5 transition hover:shadow-md">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Average GWA</div>
        <div class="text-2xl font-bold text-slate-900">{{ $allFacultyRows->avg('weighted_average') ? number_format($allFacultyRows->avg('weighted_average'), 2) : '—' }}</div>
        <div class="text-xs text-gray-400 mt-1">Across all faculty</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-5 transition hover:shadow-md">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Excellent Performers</div>
        <div class="text-2xl font-bold text-slate-900">{{ $allFacultyRows->where('performance_level', 'Excellent')->count() }}</div>
        <div class="text-xs text-gray-400 mt-1">GWA 4.50 – 5.00</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-5 transition hover:shadow-md">
        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Needs Attention</div>
        <div class="text-2xl font-bold text-slate-900">{{ $allFacultyRows->whereIn('performance_level', ['Fair', 'Poor'])->count() }}</div>
        <div class="text-xs text-gray-400 mt-1">Fair or below</div>
    </div>
</div>

{{-- Personnel Historical Trend --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3 flex-wrap">
        <div>
            <span class="font-bold text-slate-900">Historical Performance Trend Per Personnel</span>
            <p class="text-xs text-gray-500 mt-1">
                @if($selectedPersonnelUser)
                    Selected: <strong>{{ $selectedPersonnelUser->name }}</strong> (trend across all historical school years and semesters)
                @else
                    Select personnel to display historical trend.
                @endif
            </p>
        </div>
    </div>
    <div class="p-5">
        <div class="relative h-[320px] md:h-[400px]">
            <canvas id="personnelTrendChart"></canvas>
        </div>
    </div>
</div>

{{-- Charts Row --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
        <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
            <span class="font-bold text-slate-900">Performance Distribution</span>
        </div>
        <div class="p-5">
            <canvas id="distChart" height="250"></canvas>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
        <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
            <span class="font-bold text-slate-900">Department Average GWA</span>
        </div>
        <div class="p-5">
            <canvas id="deptChart" height="250"></canvas>
        </div>
    </div>
</div>

{{-- Department Summary Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
        <span class="font-bold text-slate-900">Department Summary</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Faculty Count</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Average GWA</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Performance Level</th>
                </tr>
            </thead>
            <tbody>
                @forelse($departmentSummaries as $index => $summary)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $index + 1 }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $summary['department']->name }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $summary['faculty_count'] }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $summary['average_score'] !== null ? number_format($summary['average_score'], 2) : '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if($summary['average_score'])
                            @php
                                $lvl = $summary['performance_level'];
                                $cls = match($lvl) {
                                    'Excellent' => 'bg-green-100 text-green-700',
                                    'Very Satisfactory' => 'bg-blue-100 text-blue-700',
                                    'Satisfactory' => 'bg-amber-100 text-amber-700',
                                    'Fair' => 'bg-amber-100 text-amber-700',
                                    'Poor' => 'bg-red-100 text-red-700',
                                    default => 'bg-amber-100 text-amber-700'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $cls }}">{{ $lvl }}</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">No Data</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-500 py-8 px-4">No department data available.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Faculty Performance Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
        <span class="font-bold text-slate-900">Faculty Performance Details</span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $facultyRows->total() }} records</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Student Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Dean Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Self Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Peer Avg</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Weighted GWA</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Level</th>
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
                @endphp
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($facultyRows->currentPage() - 1) * $facultyRows->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $row['user']->name }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $row['department']?->name ?? '—' }}</td>
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
                    <td colspan="10" class="text-center text-gray-500 py-8 px-4">No data for the selected filters.</td>
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
    const filterForm = document.getElementById('analyticsFilterForm');
    const schoolYearInput = document.getElementById('filter_sy');
    const semesterInput = document.getElementById('filter_sem');
    const personnelInput = document.getElementById('personnel_profile_id');

    if (filterForm) {
        if (semesterInput) {
            semesterInput.addEventListener('change', function () {
                filterForm.submit();
            });
        }

        if (personnelInput) {
            personnelInput.addEventListener('change', function () {
                filterForm.submit();
            });
        }

        if (schoolYearInput) {
            let schoolYearDebounceTimer = null;

            const submitSchoolYearFilter = function () {
                if (schoolYearDebounceTimer) {
                    clearTimeout(schoolYearDebounceTimer);
                }

                schoolYearDebounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 600);
            };

            schoolYearInput.addEventListener('input', submitSchoolYearFilter);
            schoolYearInput.addEventListener('change', function () {
                filterForm.submit();
            });
        }
    }

    const labels = @json($chartLabels);
    const data   = @json($chartData);
    const colors = ['#22c55e','#3b82f6','#eab308','#f97316','#ef4444'];

    // Distribution bar chart
    const distCtx = document.getElementById('distChart');
    if (distCtx) {
        new Chart(distCtx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Faculty Count',
                    data: data,
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
    }

    // Department average chart
    const deptCtx = document.getElementById('deptChart');
    const deptSummaries = @json($departmentSummaries);
    if (deptCtx && deptSummaries.length > 0) {
        const deptLabels = deptSummaries.map(d => d.department.name);
        const deptData   = deptSummaries.map(d => d.average_score ?? 0);
        new Chart(deptCtx, {
            type: 'bar',
            data: {
                labels: deptLabels,
                datasets: [{
                    label: 'Average GWA',
                    data: deptData,
                    backgroundColor: '#3b82f6',
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 5, grid: { color: '#f0f4f8' } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // Historical trend chart for selected personnel
    const personnelTrendCtx = document.getElementById('personnelTrendChart');
    const trendLabels = @json($historicalTrend['labels'] ?? []);
    const trendStudent = @json($historicalTrend['student'] ?? []);
    const trendDean = @json($historicalTrend['dean'] ?? []);
    const trendSelf = @json($historicalTrend['self'] ?? []);
    const trendPeer = @json($historicalTrend['peer'] ?? []);
    const trendWeighted = @json($historicalTrend['weighted'] ?? []);
    const trendWeightedLevels = @json($historicalTrend['weighted_levels'] ?? []);

    const levelBandColor = function (level) {
        const name = String(level || '').toLowerCase();
        if (name === 'excellent' || name === 'outstanding') return 'rgba(34, 197, 94, 0.10)';
        if (name === 'very satisfactory' || name === 'above average') return 'rgba(59, 130, 246, 0.10)';
        if (name === 'satisfactory' || name === 'average') return 'rgba(245, 158, 11, 0.10)';
        if (name === 'fair' || name === 'below average') return 'rgba(249, 115, 22, 0.10)';
        if (name === 'poor' || name === 'unsatisfactory') return 'rgba(239, 68, 68, 0.10)';
        return 'rgba(148, 163, 184, 0.10)';
    };

    if (personnelTrendCtx && trendLabels.length > 0) {
        const performanceBandPlugin = {
            id: 'performanceBandPlugin',
            beforeDatasetsDraw: function (chart) {
                const x = chart.scales.x;
                const y = chart.scales.y;
                if (!x || !y) return;

                const ctx = chart.ctx;
                const ticks = trendLabels.length;
                if (ticks === 0) return;

                ctx.save();
                for (let i = 0; i < ticks; i++) {
                    const center = x.getPixelForValue(i);
                    const prevCenter = i > 0 ? x.getPixelForValue(i - 1) : null;
                    const nextCenter = i < ticks - 1 ? x.getPixelForValue(i + 1) : null;

                    const left = prevCenter === null ? x.left : (prevCenter + center) / 2;
                    const right = nextCenter === null ? x.right : (center + nextCenter) / 2;
                    const width = Math.max(0, right - left);

                    ctx.fillStyle = levelBandColor(trendWeightedLevels[i] || '');
                    ctx.fillRect(left, y.top, width, y.bottom - y.top);
                }
                ctx.restore();
            }
        };

        new Chart(personnelTrendCtx, {
            plugins: [performanceBandPlugin],
            data: {
                labels: trendLabels,
                datasets: [
                    {
                        type: 'bar',
                        label: 'Student Avg',
                        data: trendStudent,
                        backgroundColor: '#3b82f6',
                        stack: 'components',
                    },
                    {
                        type: 'bar',
                        label: 'Dean Avg',
                        data: trendDean,
                        backgroundColor: '#8b5cf6',
                        stack: 'components',
                    },
                    {
                        type: 'bar',
                        label: 'Self Avg',
                        data: trendSelf,
                        backgroundColor: '#06b6d4',
                        stack: 'components',
                    },
                    {
                        type: 'bar',
                        label: 'Peer Avg',
                        data: trendPeer,
                        backgroundColor: '#a78bfa',
                        stack: 'components',
                    },
                    {
                        type: 'line',
                        label: 'Weighted GWA Trend',
                        data: trendWeighted,
                        borderColor: '#111827',
                        backgroundColor: '#111827',
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 3,
                        yAxisID: 'y',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            afterTitle: function (items) {
                                const idx = items && items.length > 0 ? items[0].dataIndex : -1;
                                if (idx < 0) return '';
                                return 'Level: ' + (trendWeightedLevels[idx] || 'N/A');
                            }
                        }
                    }
                },
                scales: {
                    x: { stacked: true, grid: { display: false } },
                    y: { beginAtZero: true, max: 5, stacked: true, grid: { color: '#f0f4f8' } }
                }
            }
        });
    }
})();
</script>
@endpush
