@extends('layouts.app')

@section('title', 'HR Dashboard')
@section('page-title', 'HR Dashboard')

@section('content')
{{-- Skeleton loading --}}
<div id="skeletonLoader" class="animate-pulse">
    {{-- Header skeleton --}}
    <div class="mb-5">
        <div class="h-7 w-3/4 bg-gray-200 rounded-xl mb-2"></div>
        <div class="h-4 w-1/2 bg-gray-200 rounded-xl"></div>
    </div>
    {{-- 4 stat cards --}}
    <div class="grid grid-cols-2 gap-3.5 mb-5">
        @for($i = 0; $i < 4; $i++)
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
            <div class="h-3 w-24 bg-gray-200 rounded-xl mb-2"></div>
            <div class="h-6 w-12 bg-gray-200 rounded-xl mb-1"></div>
            <div class="h-3 w-28 bg-gray-200 rounded-xl"></div>
        </div>
        @endfor
    </div>
    {{-- Compliance banner skeleton --}}
    <div class="h-16 w-full bg-gray-200 rounded-2xl mb-5"></div>
    {{-- Two side-by-side cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="h-5 w-44 bg-gray-200 rounded-xl"></div>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div class="h-4 w-full bg-gray-200 rounded-xl skeleton"></div>
                <div class="h-4 w-5/6 bg-gray-200 rounded-xl skeleton"></div>
                <div class="h-4 w-4/6 bg-gray-200 rounded-xl skeleton"></div>
                <div class="h-4 w-full bg-gray-200 rounded-xl skeleton"></div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-5 py-4 border-b border-gray-200">
                <div class="h-5 w-32 bg-gray-200 rounded-xl"></div>
            </div>
            <div class="px-5 py-4 space-y-2.5">
                @for($i = 0; $i < 6; $i++)
                <div class="h-9 w-full bg-gray-200 rounded-xl skeleton"></div>
                @endfor
            </div>
        </div>
    </div>
    {{-- Department list skeleton --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-5">
        <div class="px-5 py-4 border-b border-gray-200">
            <div class="h-5 w-44 bg-gray-200 rounded-xl"></div>
        </div>
        <div class="px-4 py-3 space-y-3">
            @for($i = 0; $i < 3; $i++)
            <div class="h-4 w-full bg-gray-200 rounded-xl skeleton"></div>
            @endfor
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
        <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $hr->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($period)
                Active Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
                &nbsp;<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Open</span>
            @else
                No evaluation period is currently open.
            @endif
        </p>
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 gap-3.5 mb-5">
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Faculty</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalFaculty }}</div>
        <div class="text-xs text-gray-400 mt-0.5">Active faculty members</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total Students</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalStudents }}</div>
        <div class="text-xs text-gray-400 mt-0.5">Active students</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Deans / Heads</div>
        <div class="text-2xl font-bold text-gray-900">{{ $totalDeans }}</div>
        <div class="text-xs text-gray-400 mt-0.5">Department evaluators</div>
    </div>
    <div class="stat-stagger animate-slide-up-delayed bg-white border border-gray-200 rounded-2xl shadow-sm p-4">
        <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Evaluated Faculty</div>
        <div class="text-2xl font-bold text-gray-900">{{ $facultyRows->whereNotNull('weighted_average')->count() }}</div>
        <div class="text-xs text-gray-400 mt-0.5">With performance data</div>
    </div>
</div>

@can('monitor-not-evaluated')
<div class="bg-gradient-to-r from-slate-800 to-slate-900 border border-slate-700 rounded-2xl shadow-sm p-4 mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div class="text-white">
        <div class="text-sm font-semibold">Evaluation compliance</div>
        <p class="text-xs text-slate-300 mt-0.5">See self, peer, and supervisor completion for every faculty member for the open period (student course evaluations are tracked elsewhere).</p>
    </div>
    <a href="{{ route('evaluate.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 bg-white text-slate-900 text-sm font-semibold rounded-xl hover:bg-slate-100 transition-colors shrink-0">
        Open monitoring
    </a>
</div>
@endcan

{{-- Performance Distribution Chart --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <span class="text-base font-semibold text-gray-900">Performance Distribution</span>
        </div>
        <div class="px-5 py-4">
            <canvas id="hrChart" height="240"></canvas>
        </div>
    </div>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <span class="text-base font-semibold text-gray-900">Level Summary</span>
        </div>
        <div class="px-5 py-4">
            <div class="flex flex-col gap-2.5">
                @foreach(['Excellent' => 'badge-success', 'Very Satisfactory' => 'badge-primary', 'Satisfactory' => 'badge-warning', 'Fair' => '', 'Poor' => 'badge-danger'] as $level => $badgeClass)
                <div class="flex justify-between items-center px-3.5 py-2.5 bg-gray-50 rounded-xl">
                    @if($badgeClass === 'badge-success')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">{{ $level }}</span>
                    @elseif($badgeClass === 'badge-primary')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $level }}</span>
                    @elseif($badgeClass === 'badge-warning')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">{{ $level }}</span>
                    @elseif($badgeClass === 'badge-danger')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">{{ $level }}</span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-orange-500 text-white">{{ $level }}</span>
                    @endif
                    <strong class="font-bold text-gray-900">{{ $levelCounts->get($level, 0) }}</strong>
                </div>
                @endforeach
                <div class="flex justify-between items-center px-3.5 py-2.5 bg-gray-50 rounded-xl border-t-2 border-gray-200">
                    <span class="font-bold text-gray-900">No Data Yet</span>
                    <strong class="font-bold text-gray-900">{{ $facultyRows->whereNull('weighted_average')->count() }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Non-Teaching Departments --}}
@if($nonTeachingDepts->count() > 0)
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-5">
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
        <span class="text-base font-semibold text-gray-900">Non-Teaching Departments</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $nonTeachingDepts->count() }} departments</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Personnel Count</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($nonTeachingDepts as $index => $dept)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-600">{{ $index + 1 }}</td>
                    <td class="px-4 py-3"><strong class="font-semibold text-gray-900">{{ $dept->name }}</strong></td>
                    <td class="px-4 py-3 text-gray-600">{{ $dept->users_count }}</td>
                    <td class="px-4 py-3">
                        @if($dept->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Inactive</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Faculty Performance Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
    <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
        <span class="text-base font-semibold text-gray-900">Faculty Performance Overview</span>
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">{{ $facultyRows->count() }} faculty</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50">
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Department</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Weighted GWA</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Performance Level</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($facultyRows as $index => $row)
                <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 text-gray-600">{{ $index + 1 }}</td>
                    <td class="px-4 py-3"><strong class="font-semibold text-gray-900">{{ $row['user']->name }}</strong></td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['department']?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $row['weighted_average'] !== null ? number_format($row['weighted_average'], 2) : '—' }}</td>
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
                    <td colspan="5" class="text-center text-gray-400 py-8">
                        No faculty performance data available.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
    const ctx = document.getElementById('hrChart');
    if (!ctx) return;

    const levelCounts = @json($levelCounts);
    const labels = ['Excellent', 'Very Satisfactory', 'Satisfactory', 'Fair', 'Poor'];
    const data   = labels.map(l => levelCounts[l] ?? 0);
    const colors = ['#22c55e', '#3b82f6', '#eab308', '#f97316', '#ef4444'];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{
                data,
                backgroundColor: colors,
                borderWidth: 2,
                borderColor: '#fff',
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 14, usePointStyle: true }
                }
            }
        }
    });
})();
</script>
@endpush
