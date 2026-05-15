@extends('layouts.app')

@section('title', 'Top Performers')
@section('page-title', 'Top Performers')

@section('content')
<div class="flex justify-between items-start gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Top Performer of the Department</h1>
        <p class="text-sm text-gray-500 mt-1 max-w-2xl">
            The single highest-scoring faculty in each department for the selected evaluation period.
            @if($departmentScoped)
                You are viewing your own department only.
            @else
                Admin / HR view: one winner per department across the institution.
            @endif
            Generate a printable certificate from the row's "Certificate" button.
        </p>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="p-5">
        <form method="GET" action="{{ route('top-performers.index') }}" class="flex gap-3.5 items-end flex-wrap">
            @php
                $schoolYearOptions = $periods->pluck('school_year')->filter()->unique()->values();
                if ($schoolYearOptions->isEmpty()) {
                    $y = (int) date('Y');
                    $schoolYearOptions = collect([($y - 1).'-'.$y, $y.'-'.($y + 1)]);
                }
            @endphp
            <div class="m-0 min-w-[180px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="tp_sy">School Year</label>
                <select name="school_year" id="tp_sy" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">— Select year —</option>
                    @foreach($schoolYearOptions as $sy)
                        <option value="{{ $sy }}" @selected($sy === $schoolYear)>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>
            <div class="m-0 min-w-[180px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="tp_sem">Semester</label>
                <select name="semester" id="tp_sem" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">— Select semester —</option>
                    <option value="1st"    @selected($semester === '1st')>1st Semester</option>
                    <option value="2nd"    @selected($semester === '2nd')>2nd Semester</option>
                    <option value="Summer" @selected($semester === 'Summer')>Summer</option>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm">Apply</button>
        </form>
    </div>
</div>

@if(!$hasPeriodFilter)
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 mb-4 text-sm">
        Choose a <strong>school year</strong> and <strong>semester</strong>, then click Apply, to identify each department's top performer.
    </div>
@elseif($topPerformers->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-gray-50 text-gray-700 px-4 py-3 mb-4 text-sm">
        No faculty with evaluation data {{ $departmentScoped ? 'in your department' : '' }} for <strong>{{ $schoolYear }} — {{ $semester }}</strong> yet. Once student / dean / self / peer evaluations are submitted, the dept #1 will appear here.
    </div>
@else
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
        <span class="font-semibold text-gray-900">Top performers — {{ $schoolYear }} / {{ $semester === 'Summer' ? 'Summer' : $semester.' Semester' }}</span>
        <span class="bg-amber-100 text-amber-800 px-2.5 py-1 rounded-full text-xs font-bold">{{ $topPerformers->count() }} {{ Str::plural('department', $topPerformers->count()) }}</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-5 py-3 font-semibold">#</th>
                    <th class="px-5 py-3 font-semibold">Department</th>
                    <th class="px-5 py-3 font-semibold">Top Performer</th>
                    <th class="px-5 py-3 font-semibold">Performance Level</th>
                    <th class="px-5 py-3 font-semibold">GWA</th>
                    <th class="px-5 py-3 font-semibold">Certificate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($topPerformers as $i => $row)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3 text-gray-600">{{ $i + 1 }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $row['department'] }}</td>
                    <td class="px-5 py-3 font-bold text-gray-900">{{ $row['user']->name }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $row['badge_class'] }}">{{ $row['performance_level'] }}</span>
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-900">{{ number_format($row['weighted_average'], 2) }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('top-performers.certificate', ['faculty_profile' => $row['profile']]) }}?school_year={{ urlencode($schoolYear) }}&semester={{ urlencode($semester) }}"
                           target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 bg-amber-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold hover:bg-amber-700 transition shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3a1 1 0 00-1 1v6a8 8 0 008 8h0a8 8 0 008-8V4a1 1 0 00-1-1H5zM9 21h6M12 17v4" />
                            </svg>
                            Issue Certificate
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
