@extends('layouts.app')

@section('title', 'Evaluate Faculty')
@section('page-title', 'Evaluate Faculty')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Evaluate Faculty</h1>
        <p class="text-sm text-gray-500 mt-1">
            Highest-tier performers (Excellent / Outstanding) for the period you select. Generate a printable certificate with name, department, and GWA.
        </p>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="p-5">
        <form method="GET" action="{{ route('evaluate.index') }}" class="flex gap-3.5 items-end flex-wrap">
            <div class="m-0 min-w-[160px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="cert_sy">School year</label>
                <input type="text" name="school_year" id="cert_sy" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. 2025-2026" value="{{ $schoolYear }}">
            </div>
            <div class="m-0 min-w-[160px]">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="cert_sem">Semester</label>
                <select name="semester" id="cert_sem" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">Select semester</option>
                    <option value="1st" {{ $semester === '1st' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd" {{ $semester === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                    <option value="Summer" {{ $semester === 'Summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm">Apply</button>
        </form>
        @if($period)
            <p class="text-xs text-gray-500 mt-3">Open evaluation period: {{ $period->school_year }} — {{ format_semester($period->semester) }} (defaults apply when fields are left empty).</p>
        @endif
    </div>
</div>

@if(! $hasPeriodFilter)
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 mb-4 text-sm">
        Choose a <strong>school year</strong> and <strong>semester</strong>, then click Apply, to list personnel with Excellent or Outstanding performance and generate certificates.
    </div>
@elseif($excellentFaculty->isEmpty())
    <div class="rounded-xl border border-gray-200 bg-gray-50 text-gray-700 px-4 py-3 mb-4 text-sm">
        No personnel with <strong>Excellent</strong> (teaching / academic admin rubrics) or <strong>Outstanding</strong> (non-teaching) for this period.
    </div>
@endif

@if($hasPeriodFilter && $excellentFaculty->isNotEmpty())
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
        <span class="font-semibold text-gray-900">Excellent / Outstanding — {{ $schoolYear }}, {{ $semester }}</span>
        <span class="bg-green-100 text-green-800 px-2.5 py-1 rounded-full text-xs font-bold">{{ $excellentFaculty->count() }} personnel</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-5 py-3 font-semibold">#</th>
                    <th class="px-5 py-3 font-semibold">Name</th>
                    <th class="px-5 py-3 font-semibold">Department</th>
                    <th class="px-5 py-3 font-semibold">Level</th>
                    <th class="px-5 py-3 font-semibold">GWA</th>
                    <th class="px-5 py-3 font-semibold">Certificate</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($excellentFaculty as $index => $row)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3 text-gray-600">{{ $index + 1 }}</td>
                    <td class="px-5 py-3 font-bold text-gray-900">{{ $row['user']->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $row['department'] }}</td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $row['badge_class'] }}">{{ $row['performance_level'] }}</span>
                    </td>
                    <td class="px-5 py-3 font-semibold text-gray-900">{{ number_format($row['weighted_average'], 2) }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('certificates.performance-excellent', ['faculty_profile' => $row['profile']]) }}?school_year={{ urlencode($schoolYear) }}&semester={{ urlencode($semester) }}"
                           target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 bg-indigo-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold hover:bg-indigo-700 transition shadow-sm">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Generate
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
