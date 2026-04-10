@extends('layouts.app')

@section('title', 'Evaluate Faculty')
@section('page-title', 'Evaluate Faculty')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Faculty Evaluation</h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($period)
                Period: {{ $period->school_year }} &mdash; {{ format_semester($period->semester) }}
            @else
                No evaluation period is currently open.
            @endif
        </p>
    </div>
</div>

@if(!$period)
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-4 py-3 mb-4">
        Evaluations are currently closed. Please wait for the administrator to open an evaluation period.
    </div>
@endif

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
        <span class="font-semibold text-gray-900">Self Evaluation</span>
    </div>
    <div class="p-5">
        @if($deanProfile)
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div class="flex items-center gap-3.5">
                    <div class="w-12 h-12 rounded-full bg-blue-600 text-white grid place-items-center text-lg font-bold shrink-0">
                        {{ strtoupper(substr($dean->name, 0, 1)) }}
                    </div>
                    <div>
                        <div class="font-bold text-gray-900">{{ $dean->name }}</div>
                        <div class="text-gray-400 text-sm">Self Assessment (Dean/Head)</div>
                    </div>
                </div>
                <div>
                    @if($hasSelfEvaluated)
                        <span class="bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm font-bold">Completed</span>
                    @elseif($period && $canSelfEvaluate)
                        <a href="{{ route('evaluate.show', ['type' => 'self', 'facultyId' => $dean->id]) }}"
                           class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm">
                            Start Self Evaluation
                        </a>
                    @else
                        <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">Unavailable</span>
                    @endif
                </div>
            </div>
        @else
            <div class="text-center text-gray-400 py-6">
                No faculty profile found for this dean/head account.
            </div>
        @endif
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
        <span class="font-semibold text-gray-900">Faculty in Your Department</span>
        <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">{{ $faculty->count() }} pending</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-5 py-3 font-semibold">#</th>
                    <th class="px-5 py-3 font-semibold">Name</th>
                    <th class="px-5 py-3 font-semibold">Email</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                    <th class="px-5 py-3 font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($faculty as $index => $item)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3">{{ $index + 1 }}</td>
                    <td class="px-5 py-3 font-bold text-gray-900">{{ $item['user']->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $item['user']->email }}</td>
                    <td class="px-5 py-3">
                        @if($item['has_evaluated'])
                            <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">Evaluated</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">Pending</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @can('submit-dean-evaluation')
                            @if($period && !$item['has_evaluated'])
                                <a href="{{ route('evaluate.show', ['type' => 'dean', 'facultyId' => $item['user']->id]) }}"
                                   class="inline-block bg-blue-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold hover:bg-blue-700 transition shadow-sm">
                                    Evaluate
                                </a>
                            @elseif($item['has_evaluated'])
                                <span class="text-gray-400 text-sm">Completed</span>
                            @else
                                <span class="text-gray-400 text-sm">Unavailable</span>
                            @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-gray-400 py-8 px-5">
                        No faculty records found in your department.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3 flex-wrap">
        <div class="font-semibold text-gray-900">Student Evaluation Monitoring</div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('evaluate.index', ['student_status' => 'evaluated']) }}"
               class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $selectedStudentStatus === 'evaluated' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Evaluated ({{ $studentStatusCounts['evaluated'] ?? 0 }})
            </a>
            <a href="{{ route('evaluate.index', ['student_status' => 'non_evaluative']) }}"
               class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $selectedStudentStatus === 'non_evaluative' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Non-Evaluative ({{ $studentStatusCounts['non_evaluative'] ?? 0 }})
            </a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-5 py-3 font-semibold">#</th>
                    <th class="px-5 py-3 font-semibold">Department</th>
                    <th class="px-5 py-3 font-semibold">Student ID</th>
                    <th class="px-5 py-3 font-semibold">Name</th>
                    <th class="px-5 py-3 font-semibold">Course / Year / Section</th>
                    <th class="px-5 py-3 font-semibold">Submissions</th>
                    <th class="px-5 py-3 font-semibold">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($studentItems as $index => $item)
                @php($profile = $item['profile'])
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3">{{ $index + 1 }}</td>
                    <td class="px-5 py-3 text-gray-700">{{ $item['department_name'] }}</td>
                    <td class="px-5 py-3 font-medium text-gray-700">{{ $profile->student_id ?? '-' }}</td>
                    <td class="px-5 py-3 font-bold text-gray-900">{{ $item['user']->name }}</td>
                    <td class="px-5 py-3 text-gray-600">
                        {{ $profile->course ?? '-' }} /
                        {{ $profile->year_level ?? '-' }} /
                        {{ $profile->section ?? '-' }}
                    </td>
                    <td class="px-5 py-3 text-gray-700">{{ $item['submission_count'] }}</td>
                    <td class="px-5 py-3">
                        @if($item['has_evaluated'])
                            <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">Evaluated</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">Non-Evaluative</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-400 py-8 px-5">
                        @if($period)
                            No students found for this filter in your department.
                        @else
                            No open evaluation period. Student monitoring will update once a period is open.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
