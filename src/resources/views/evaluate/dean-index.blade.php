@extends('layouts.app')

@section('title', 'Evaluate Faculty')
@section('page-title', 'Evaluate Faculty')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            @if($isHrWideMonitor ?? false)
                Evaluation compliance monitoring
            @elseif($evaluatesInstitutionLeaders ?? false)
                Evaluate academic leaders
            @else
                Faculty Evaluation
            @endif
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($period)
                Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
            @else
                No evaluation period is currently open.
            @endif
            @if($isHrWideMonitor ?? false)
                <span class="block mt-1 text-gray-600">Institution-wide view for <strong>Human Resources</strong>. A faculty member is <strong>complete</strong> only when <strong>self</strong>, <strong>peer</strong>, and <strong>supervisor (admin)</strong> evaluations are all submitted for this period. <strong>Student</strong> evaluations are not part of this checklist.</span>
            @elseif($evaluatesInstitutionLeaders ?? false)
                <span class="block mt-1 text-gray-600">Evaluate <strong>Dean / Head</strong> (teaching) and <strong>Administrator / Head</strong> (non-teaching) across all departments. Each row shows the <strong>department or office</strong> that person leads.</span>
            @endif
        </p>
    </div>
</div>

@if(!$period)
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-4 py-3 mb-4">
        Evaluations are currently closed. Please wait for the administrator to open an evaluation period.
    </div>
@endif

@if(!($isHrWideMonitor ?? false))
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
                        <div class="text-gray-500 text-sm">{{ $deanProfile->department?->name ?? '—' }}</div>
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
@endif

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3 flex-wrap">
        <span class="font-semibold text-gray-900">
            @if($isHrWideMonitor ?? false)
                Faculty — self, peer &amp; supervisor (all departments)
            @else
                Faculty in Your Department
            @endif
        </span>
        @if($isHrWideMonitor ?? false)
            <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-xs font-bold">{{ $facultyStatusCounts['pending'] ?? 0 }} incomplete (missing at least one)</span>
        @else
            <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">{{ $facultyPendingCount ?? $faculty->count() }} pending</span>
        @endif
    </div>
    @if($isHrWideMonitor ?? false)
    <div class="px-5 py-3 border-b border-gray-100 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide mr-1">Filter</span>
        <a href="{{ route('evaluate.index', array_merge(request()->only('student_status'), ['faculty_status' => 'all'])) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ ($selectedFacultyStatus ?? 'all') === 'all' ? 'bg-slate-700 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            All ({{ $facultyStatusCounts['all'] ?? 0 }})
        </a>
        <a href="{{ route('evaluate.index', array_merge(request()->only('student_status'), ['faculty_status' => 'evaluated'])) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ ($selectedFacultyStatus ?? '') === 'evaluated' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            Complete ({{ $facultyStatusCounts['evaluated'] ?? 0 }})
        </a>
        <a href="{{ route('evaluate.index', array_merge(request()->only('student_status'), ['faculty_status' => 'pending'])) }}"
           class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ ($selectedFacultyStatus ?? '') === 'pending' ? 'bg-amber-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
            Incomplete ({{ $facultyStatusCounts['pending'] ?? 0 }})
        </a>
    </div>
    @endif
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs tracking-wider">
                <tr>
                    <th class="px-5 py-3 font-semibold">#</th>
                    <th class="px-5 py-3 font-semibold">Department / office</th>
                    @if($evaluatesInstitutionLeaders ?? false)
                    <th class="px-5 py-3 font-semibold">Role / position</th>
                    @endif
                    <th class="px-5 py-3 font-semibold">Name</th>
                    <th class="px-5 py-3 font-semibold">Email</th>
                    @if($isHrWideMonitor ?? false)
                    <th class="px-5 py-3 font-semibold">Self</th>
                    <th class="px-5 py-3 font-semibold">Peer</th>
                    <th class="px-5 py-3 font-semibold">Supervisor</th>
                    <th class="px-5 py-3 font-semibold">Overall</th>
                    @else
                    <th class="px-5 py-3 font-semibold">Status</th>
                    @endif
                    <th class="px-5 py-3 font-semibold">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($faculty as $index => $item)
                <tr class="hover:bg-gray-50 transition">
                    <td class="px-5 py-3">{{ $index + 1 }}</td>
                    <td class="px-5 py-3 text-gray-800 font-medium">{{ $item['profile']?->department?->name ?? '—' }}</td>
                    @if($evaluatesInstitutionLeaders ?? false)
                    <td class="px-5 py-3 text-gray-700 text-xs leading-snug max-w-[14rem]">
                        {{ $item['profile']?->departmentPositionLabel() ?? '—' }}
                    </td>
                    @endif
                    <td class="px-5 py-3 font-bold text-gray-900">{{ $item['user']->name }}</td>
                    <td class="px-5 py-3 text-gray-600">{{ $item['user']->email }}</td>
                    @if($isHrWideMonitor ?? false)
                    <td class="px-5 py-3">
                        @if(!empty($item['has_self']))
                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-[11px] font-bold">Done</span>
                        @else
                            <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[11px] font-bold">Missing</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if(!empty($item['has_peer']))
                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-[11px] font-bold">Done</span>
                        @else
                            <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[11px] font-bold">Missing</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if(!empty($item['has_supervisor']))
                            <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded-full text-[11px] font-bold">Done</span>
                        @else
                            <span class="bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full text-[11px] font-bold">Missing</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        @if($item['has_evaluated'])
                            <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">Complete</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">Incomplete</span>
                        @endif
                    </td>
                    @else
                    <td class="px-5 py-3">
                        @if($item['has_evaluated'])
                            <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">Evaluated</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">Pending</span>
                        @endif
                    </td>
                    @endif
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
                        @else
                            @if($isHrWideMonitor ?? false)
                                <span class="text-gray-400 text-xs">—</span>
                            @endif
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ ($isHrWideMonitor ?? false) ? 9 : (($evaluatesInstitutionLeaders ?? false) ? 7 : 6) }}" class="text-center text-gray-400 py-8 px-5">
                        @if($evaluatesInstitutionLeaders ?? false)
                            No personnel with Dean/Head position found, or no faculty profiles yet.
                        @elseif($isHrWideMonitor ?? false)
                            No faculty match this filter.
                        @else
                            No faculty records found in your department.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!($evaluatesInstitutionLeaders ?? false) && !($isHrWideMonitor ?? false))
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3 flex-wrap">
        <div class="font-semibold text-gray-900">Student Evaluation Monitoring</div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('evaluate.index', array_merge(request()->only('faculty_status'), ['student_status' => 'evaluated'])) }}"
               class="px-3 py-1.5 rounded-full text-xs font-semibold transition {{ $selectedStudentStatus === 'evaluated' ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}">
                Evaluated ({{ $studentStatusCounts['evaluated'] ?? 0 }})
            </a>
            <a href="{{ route('evaluate.index', array_merge(request()->only('faculty_status'), ['student_status' => 'non_evaluative'])) }}"
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
                            @if($isHrWideMonitor ?? false)
                                No students found for this filter.
                            @else
                                No students found for this filter in your department.
                            @endif
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
@else
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex flex-wrap justify-between items-center gap-3">
        <div>
            <div class="font-semibold text-gray-900">Department Head/Dean Evaluation Monitoring</div>
            <p class="text-xs text-gray-500 mt-1">Department or office each leader belongs to (same as table above).</p>
        </div>
        <span class="bg-slate-100 text-slate-700 px-2.5 py-1 rounded-full text-xs font-bold">{{ $faculty->count() }} leaders</span>
    </div>
    <div class="p-5">
        @if($faculty->isEmpty())
            <p class="text-center text-gray-400 py-8">No department heads or deans to display yet.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($faculty as $item)
                <div class="rounded-xl border border-gray-200 bg-gray-50/80 p-4 flex flex-col gap-2 hover:border-blue-200 hover:bg-white transition shadow-sm">
                    <div class="text-xs font-bold text-blue-700 uppercase tracking-wide leading-snug">
                        {{ $item['profile']?->department?->name ?? '—' }}
                    </div>
                    <div class="font-bold text-gray-900 text-sm">{{ $item['user']->name }}</div>
                    <div class="text-xs text-gray-500 break-all">{{ $item['user']->email }}</div>
                    <div class="flex flex-wrap items-center gap-2 mt-1 pt-2 border-t border-gray-200">
                        @if($item['has_evaluated'])
                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Evaluated</span>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-[10px] font-bold">Pending</span>
                        @endif
                        @can('submit-dean-evaluation')
                            @if($period && !$item['has_evaluated'])
                                <a href="{{ route('evaluate.show', ['type' => 'dean', 'facultyId' => $item['user']->id]) }}"
                                   class="ml-auto text-xs font-semibold text-blue-600 hover:text-blue-800">Evaluate →</a>
                            @endif
                        @endcan
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endif
@endsection
