@extends('layouts.app')

@section('title', 'Dean Dashboard')
@section('page-title', 'Dean Dashboard')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $dean->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($period)
                Current Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
                &nbsp;<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Open</span>
            @else
                No evaluation period is currently open.
            @endif
        </p>
    </div>
    <div>
        <a href="{{ route('evaluate.index') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors">Evaluate Faculty</a>
    </div>
</div>

<a href="{{ route('model-training.index') }}" class="mb-5 flex items-center justify-between gap-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm transition-colors hover:bg-blue-100">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-600 text-white">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h6v6H3z"/><path d="M15 3h6v6h-6z"/><path d="M3 15h6v6H3z"/><path d="M15 15h6v6h-6z"/></svg>
        </div>
        <div>

        </div>
    </div>
    <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold">Open</span>
</a>

{{-- Stats Row --}}
<div class="grid grid-cols-2 gap-3.5 mb-5">
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-100 text-blue-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Faculty</div>
            <div class="text-2xl font-bold text-gray-900">{{ $allFacultyData->count() }}</div>
        </div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-green-100 text-green-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Evaluated</div>
            <div class="text-2xl font-bold text-gray-900">{{ $allFacultyData->where('has_evaluated', true)->count() }}</div>
        </div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-100 text-amber-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Pending</div>
            <div class="text-2xl font-bold text-gray-900">{{ $allFacultyData->where('has_evaluated', false)->count() }}</div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5 items-start">
    {{-- Performance Chart --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
            <span class="text-sm font-semibold text-gray-900">Performance Distribution</span>
        </div>
        <div class="px-4 py-3">
            <canvas id="deptChart" height="150"></canvas>
        </div>
    </div>

    {{-- Quick Legend --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
            <span class="text-sm font-semibold text-gray-900">Performance Scale</span>
        </div>
        <div class="px-4 py-3">
            <div class="flex flex-col gap-1.5">
                <div class="flex justify-between items-center px-3 py-1.5 bg-gray-50 rounded-lg text-sm">
                    <span class="text-gray-700">Excellent</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">4.50 – 5.00</span>
                </div>
                <div class="flex justify-between items-center px-3 py-1.5 bg-gray-50 rounded-lg text-sm">
                    <span class="text-gray-700">Very Satisfactory</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">3.50 – 4.49</span>
                </div>
                <div class="flex justify-between items-center px-3 py-1.5 bg-gray-50 rounded-lg text-sm">
                    <span class="text-gray-700">Satisfactory</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">2.50 – 3.49</span>
                </div>
                <div class="flex justify-between items-center px-3 py-1.5 bg-gray-50 rounded-lg text-sm">
                    <span class="text-gray-700">Fair</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-500 text-white">1.75 – 2.49</span>
                </div>
                <div class="flex justify-between items-center px-3 py-1.5 bg-gray-50 rounded-lg text-sm">
                    <span class="text-gray-700">Poor</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Below 1.75</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Faculty Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
        <span class="text-base font-semibold text-gray-900">Pending Faculty Evaluations</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $facultyData->total() }} pending</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Weighted GWA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Performance Level</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Evaluation Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Action</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Interventions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($facultyData as $row)
                @php
                    $personnelForIntervention = $row['profile']->evaluationCriteriaPersonnelType();
                    $showInterventionLink = $period && $row['performance_level']
                        && \App\Services\EvaluationService::qualifiesForPerformanceIntervention($row['performance_level'], $personnelForIntervention);
                @endphp
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-600">{{ ($facultyData->currentPage() - 1) * $facultyData->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3"><strong class="font-semibold text-gray-900">{{ $row['user']->name }}</strong></td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['weighted_average'] !== null ? number_format($row['weighted_average'], 2) : '—' }}</td>
                    <td class="px-4 py-3">
                        @if($row['performance_level'])
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $row['badge_class'] }}">{{ $row['performance_level'] }}</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($row['has_evaluated'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Evaluated</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Not Yet Evaluated</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if(!$row['has_evaluated'] && $period)
                            <a href="{{ route('evaluate.show', ['type' => 'dean', 'facultyId' => $row['user']->id]) }}"
                               class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition-colors">Evaluate</a>
                        @else
                            <span class="text-gray-400 text-sm">Done</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($showInterventionLink)
                            <a href="{{ route('faculty.intervention-suggestions', ['faculty_profile' => $row['profile']]) }}?school_year={{ urlencode($period->school_year) }}&semester={{ urlencode($period->semester) }}"
                               class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-100 text-rose-900 hover:bg-rose-200 transition">Plan</a>
                        @else
                            <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-400 py-8">
                        No pending faculty evaluations in your department.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($facultyData->hasPages())
    <div class="px-5 py-4">
        {{ $facultyData->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const ctx = document.getElementById('deptChart');
    if (!ctx) return;

    const facultyData = @json($allFacultyData);
    const levels = ['Excellent', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Poor'];
    const counts = levels.map(l => facultyData.filter(r => r.performance_level === l).length);
    const colors = ['#22c55e','#3b82f6','#eab308','#f97316','#ef4444'];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: levels,
            datasets: [{
                label: 'Faculty Count',
                data: counts,
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
