@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
{{-- Skeleton loading --}}
<div id="skeletonLoader" class="animate-pulse">
    {{-- Header skeleton --}}
    <div class="mb-5">
        <div class="h-7 w-3/4 bg-gray-200 rounded-xl mb-2"></div>
        <div class="h-4 w-1/2 bg-gray-200 rounded-xl"></div>
    </div>
    {{-- Model Training banner skeleton --}}
    <div class="h-16 w-full bg-gray-200 rounded-2xl mb-5"></div>
    {{-- 4 stat cards grid --}}
    <div class="grid grid-cols-2 gap-3.5 mb-5">
        @for($i = 0; $i < 4; $i++)
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
            <div class="w-10 h-10 rounded-xl bg-gray-200 skeleton"></div>
            <div class="flex-1">
                <div class="h-3 w-20 bg-gray-200 rounded-xl mb-2"></div>
                <div class="h-6 w-12 bg-gray-200 rounded-xl"></div>
            </div>
        </div>
        @endfor
    </div>
    {{-- Two side-by-side cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-4 py-2.5 border-b border-gray-200">
                <div class="h-4 w-40 bg-gray-200 rounded-xl"></div>
            </div>
            <div class="px-4 py-3 space-y-3">
                <div class="h-4 w-full bg-gray-200 rounded-xl skeleton"></div>
                <div class="h-4 w-5/6 bg-gray-200 rounded-xl skeleton"></div>
                <div class="h-4 w-4/6 bg-gray-200 rounded-xl skeleton"></div>
                <div class="h-4 w-full bg-gray-200 rounded-xl skeleton"></div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-4 py-2.5 border-b border-gray-200">
                <div class="h-4 w-36 bg-gray-200 rounded-xl"></div>
            </div>
            <div class="px-4 py-3 space-y-2">
                @for($i = 0; $i < 4; $i++)
                <div class="flex items-center justify-between">
                    <div class="h-4 w-16 bg-gray-200 rounded-xl skeleton"></div>
                    <div class="h-4 w-10 bg-gray-200 rounded-xl"></div>
                    <div class="h-4 w-10 bg-gray-200 rounded-xl"></div>
                </div>
                @endfor
            </div>
        </div>
    </div>
    {{-- Faculty table skeleton --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200">
            <div class="h-5 w-48 bg-gray-200 rounded-xl"></div>
        </div>
        <div class="px-4 py-3 space-y-3">
            @for($i = 0; $i < 5; $i++)
            <div class="h-4 w-full bg-gray-200 rounded-xl skeleton"></div>
            @endfor
        </div>
    </div>
</div>

{{-- Real content (hidden until loaded) --}}
<div id="realContent" style="display:none;">
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ auth()->user()->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($period)
                Active Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
            @else
                No evaluation period is currently open.
            @endif
        </p>
    </div>
</div>

<a href="{{ route('model-training.index') }}" class="mb-5 flex items-center justify-between gap-4 rounded-2xl border border-blue-200 bg-blue-50 p-4 shadow-sm transition-colors hover:bg-blue-100">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-600 text-white">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3h6v6H3z"/><path d="M15 3h6v6h-6z"/><path d="M3 15h6v6H3z"/><path d="M15 15h6v6h-6z"/></svg>
        </div>
        <div>
            <div class="text-sm font-semibold text-blue-900">Model Training</div>
            <div class="text-xs text-blue-800">Train Random Forest for all terms or a selected semester/year.</div>
        </div>
    </div>
    <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-semibold">Open</span>
</a>

{{-- Stats Grid --}}
<div class="grid grid-cols-2 gap-3.5 mb-5">
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-100 text-blue-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Departments</div>
            <div class="text-2xl font-bold text-gray-900">{{ $departments->count() }}</div>
        </div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-green-100 text-green-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Faculty</div>
            <div class="text-2xl font-bold text-gray-900">{{ $totalFaculty }}</div>
        </div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-100 text-amber-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Students</div>
            <div class="text-2xl font-bold text-gray-900">{{ $totalStudents }}</div>
        </div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center {{ $period ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Evaluation</div>
            <div class="text-base font-bold">
                @if($period)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Open</span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Closed</span>
                @endif
            </div>
            <div class="text-xs text-gray-400 mt-0.5">
                @if($period)
                    {{ $period->start_date }} &ndash; {{ $period->end_date }}
                @else
                    No active period
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Performance Chart + Department Overview --}}
@if($allFacultyData->count() > 0)
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5 items-start">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
            <span class="text-sm font-semibold text-gray-900">Performance Distribution</span>
        </div>
        <div class="px-4 py-3">
            <canvas id="performanceChart" height="150"></canvas>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="px-4 py-2.5 border-b border-gray-200 flex items-center justify-between">
            <span class="text-sm font-semibold text-gray-900">Department Overview</span>
            <span class="text-xs text-gray-400">{{ $departments->count() }} depts</span>
        </div>
        <div class="max-h-60 overflow-y-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide sticky top-0 bg-gray-50 z-10">Dept</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide sticky top-0 bg-gray-50 z-10">Faculty</th>
                        <th class="px-3 py-2 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide sticky top-0 bg-gray-50 z-10">Students</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($departments as $dept)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-3 py-1.5"><strong class="font-semibold text-gray-900">{{ $dept->code }}</strong></td>
                        <td class="px-3 py-1.5 text-center text-gray-600">{{ $dept->faculty_count }}</td>
                        <td class="px-3 py-1.5 text-center text-gray-600">{{ $dept->student_count }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Faculty Performance Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
        <span class="text-base font-semibold text-gray-900">Faculty Performance Summary</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $facultyData->total() }} faculty</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Student Avg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Dean Avg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Self Avg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Peer Avg</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Final GWA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Level</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($facultyData as $row)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-600">{{ ($facultyData->currentPage() - 1) * $facultyData->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3"><strong class="font-semibold text-gray-900">{{ $row['user']->name }}</strong></td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['department']?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['components']['student'] !== null ? number_format($row['components']['student'], 2) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['components']['dean'] !== null ? number_format($row['components']['dean'], 2) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['components']['self'] !== null ? number_format($row['components']['self'], 2) : '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['components']['peer'] !== null ? number_format($row['components']['peer'], 2) : '—' }}</td>
                    <td class="px-4 py-3"><strong class="font-semibold text-gray-900">{{ $row['weighted_average'] !== null ? number_format($row['weighted_average'], 2) : '—' }}</strong></td>
                    <td class="px-4 py-3">
                        @if($row['performance_level'])
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $row['badge_class'] }}">{{ $row['performance_level'] }}</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">Pending</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-gray-400 py-8">
                        No faculty performance data available for the current period.
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
    const canvas = document.getElementById('performanceChart');
    if (!canvas) return;

    const facultyData = @json($allFacultyData);
    const levels = ['Excellent', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Poor'];
    const counts = levels.map(level =>
        facultyData.filter(row => row.performance_level === level).length
    );
    const colors = ['#22c55e', '#3b82f6', '#eab308', '#f97316', '#ef4444'];

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: levels,
            datasets: [{
                label: 'Number of Faculty',
                data: counts,
                backgroundColor: colors,
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index' }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1 },
                    grid: { color: '#f0f4f8' }
                },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
@endpush
