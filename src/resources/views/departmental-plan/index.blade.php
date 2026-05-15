@extends('layouts.app')

@section('title', 'Departmental Plan')
@section('page-title', 'Departmental Plan')

@php
    $priorityClass = [
        'high'   => 'bg-rose-100 text-rose-700 ring-rose-200',
        'medium' => 'bg-amber-100 text-amber-700 ring-amber-200',
        'low'    => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
    ];
    $statusClass = [
        'pending'     => 'bg-slate-100 text-slate-700 ring-slate-200',
        'in_progress' => 'bg-blue-100 text-blue-700 ring-blue-200',
        'completed'   => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        'cancelled'   => 'bg-gray-100 text-gray-500 ring-gray-200',
    ];
    $categoryLabel = [
        'recognition'          => 'Recognition',
        'sustained_excellence' => 'Sustained Excellence',
        'training'             => 'Training',
        'coaching'             => 'Coaching',
        'pip'                  => 'Performance Improvement Plan',
        'promotion'            => 'Promotion',
        'reassignment'         => 'Reassignment',
        'retention'            => 'Retention',
        'dept_wide'            => 'Department-wide',
    ];
@endphp

@section('content')
<div class="flex justify-between items-start gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Departmental Plan</h1>
        <p class="text-sm text-gray-500 mt-1">
            {{ $department?->name ?? 'No department selected' }}
            @if($schoolYear && $semester)
                &mdash; {{ $schoolYear }} / {{ $semester }}
            @endif
        </p>
        <p class="text-xs text-gray-500 mt-1 max-w-2xl">
            Auto-generated from this department's evaluation results. Items below combine each faculty's performance level
            with the dean's retention/promotion/reassignment recommendation. Mark items in-progress as you act on them.
        </p>
    </div>

    <form method="GET" action="{{ route('departmental-plan.index') }}" class="flex items-end gap-2 flex-wrap">
        @if($canPickDept)
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Department</label>
                <select name="department_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm min-w-[12rem]">
                    <option value="">— Select —</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" @selected((int) $d->id === (int) ($department?->id))>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        @php
            // School year options: prefer real periods; otherwise offer a small
            // window around the current year so the dropdown isn't blank.
            $schoolYearOptions = $periods->pluck('school_year')->filter()->unique()->values();
            if ($schoolYearOptions->isEmpty()) {
                $y = (int) date('Y');
                $schoolYearOptions = collect([
                    ($y - 1) . '-' . $y,
                    $y . '-' . ($y + 1),
                ]);
            }

            // Semester options: canonical 1st/2nd/Summer used across the app.
            $semesterOptions = ['1st' => '1st Semester', '2nd' => '2nd Semester', 'Summer' => 'Summer'];
        @endphp
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">School Year</label>
            <select name="school_year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">— Select year —</option>
                @foreach($schoolYearOptions as $sy)
                    <option value="{{ $sy }}" @selected($sy === $schoolYear)>{{ $sy }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Semester</label>
            <select name="semester" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                <option value="">— Select semester —</option>
                @foreach($semesterOptions as $val => $label)
                    <option value="{{ $val }}" @selected($val === $semester)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded-lg text-sm font-semibold border border-gray-300">
            View
        </button>
    </form>
</div>

@if($periods->isEmpty())
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-4 py-3 mb-4 flex items-center justify-between gap-3 flex-wrap">
        <div>
            <strong>No evaluation periods configured yet.</strong>
            A plan can only be generated against a defined school year &amp; semester.
            @can('manage-evaluation-periods')
                Create one to get started.
            @else
                Please ask an administrator to open an evaluation period.
            @endcan
        </div>
        @can('manage-evaluation-periods')
            <a href="{{ route('evaluation-periods.index') }}"
               class="px-3 py-2 text-sm font-semibold rounded-lg bg-amber-600 hover:bg-amber-700 text-white shadow-sm whitespace-nowrap">
                Manage evaluation periods
            </a>
        @endcan
    </div>
@elseif(!$department)
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-4 py-3 mb-4">
        @if($canPickDept)
            Select a department above to view or generate a plan.
        @else
            Your account isn't assigned to a department. Please contact your administrator to assign one before using this page.
        @endif
    </div>
@elseif(!$schoolYear || !$semester)
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-4 py-3 mb-4">
        Select a school year and semester to view or generate a plan.
    </div>
@endif

@if($plan)
    @php $rollUp = $plan->roll_up ?? []; @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
        <div class="bg-white rounded-2xl border border-gray-200 p-4">
            <div class="text-xs uppercase tracking-wider text-gray-500">Faculty in Scope</div>
            <div class="text-2xl font-bold text-gray-900 mt-1">{{ $rollUp['total'] ?? 0 }}</div>
            <div class="text-xs text-gray-500 mt-0.5">{{ $rollUp['evaluated'] ?? 0 }} with evaluation data</div>
        </div>
        <div class="bg-white rounded-2xl border border-rose-200 p-4">
            <div class="text-xs uppercase tracking-wider text-rose-600">High Priority</div>
            <div class="text-2xl font-bold text-rose-700 mt-1">{{ $rollUp['by_priority']['high'] ?? 0 }}</div>
            <div class="text-xs text-rose-500 mt-0.5">Coaching / PIP / Reassignment</div>
        </div>
        <div class="bg-white rounded-2xl border border-amber-200 p-4">
            <div class="text-xs uppercase tracking-wider text-amber-600">Medium Priority</div>
            <div class="text-2xl font-bold text-amber-700 mt-1">{{ $rollUp['by_priority']['medium'] ?? 0 }}</div>
            <div class="text-xs text-amber-500 mt-0.5">Skills Enhancement</div>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-200 p-4">
            <div class="text-xs uppercase tracking-wider text-emerald-600">Low Priority</div>
            <div class="text-2xl font-bold text-emerald-700 mt-1">{{ $rollUp['by_priority']['low'] ?? 0 }}</div>
            <div class="text-xs text-emerald-500 mt-0.5">Recognition / Sustain</div>
        </div>
    </div>

    {{-- Plan header card --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-4 border-b border-gray-200 flex items-start justify-between gap-3 flex-wrap">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-semibold text-gray-900">Plan summary</span>
                    <span class="text-[10px] uppercase tracking-wider px-2 py-0.5 rounded-full ring-1 ring-inset
                        {{ ['draft' => 'bg-slate-100 text-slate-700 ring-slate-200',
                            'active' => 'bg-blue-100 text-blue-700 ring-blue-200',
                            'completed' => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
                            'archived' => 'bg-gray-100 text-gray-500 ring-gray-200'][$plan->status] ?? 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                        {{ ucfirst($plan->status) }}
                    </span>
                    <span class="text-xs text-gray-500">Generated {{ $plan->created_at?->diffForHumans() }}</span>
                    @if($canPickDept && $plan->dean)
                        <span class="text-xs text-gray-500">by <strong>{{ $plan->dean->name }}</strong></span>
                    @endif
                </div>
                <p class="text-sm text-gray-700 mt-2">{{ $plan->summary }}</p>
            </div>

            <div class="flex items-center gap-2">
                @if($canEditPlan && in_array($plan->status, ['draft', 'active'], true))
                    <form method="POST" action="{{ route('departmental-plan.status', $plan) }}" class="inline">
                        @csrf
                        <input type="hidden" name="status" value="{{ $plan->status === 'draft' ? 'active' : 'completed' }}">
                        <button type="submit"
                            class="px-3 py-2 text-sm font-semibold rounded-lg bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                            {{ $plan->status === 'draft' ? 'Activate Plan' : 'Mark Plan Completed' }}
                        </button>
                    </form>
                @endif

                @if($canGenerate)
                    <form method="POST" action="{{ route('departmental-plan.generate') }}" class="inline"
                          onsubmit="return confirm('Regenerate the plan? The current draft for this period will be archived.');">
                        @csrf
                        <input type="hidden" name="school_year"   value="{{ $schoolYear }}">
                        <input type="hidden" name="semester"      value="{{ $semester }}">
                        <input type="hidden" name="department_id" value="{{ $department->id }}">
                        <button type="submit"
                            class="px-3 py-2 text-sm font-semibold rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 border border-gray-300">
                            Regenerate
                        </button>
                    </form>
                @endif
            </div>
        </div>

        @if(!empty($rollUp['by_level']))
            <div class="px-5 py-3 border-b border-gray-100 bg-gray-50">
                <div class="text-xs uppercase tracking-wider text-gray-500 mb-2">Performance distribution</div>
                <div class="flex flex-wrap gap-2 text-xs">
                    @foreach(['excellent' => 'Excellent', 'very_good' => 'Very Good', 'good' => 'Good', 'fair' => 'Fair', 'poor' => 'Poor', 'unknown' => 'No data'] as $key => $label)
                        @php $count = $rollUp['by_level'][$key] ?? 0; @endphp
                        @if($count > 0)
                            <span class="px-2.5 py-1 rounded-full bg-white border border-gray-200 text-gray-700">
                                {{ $label }}: <strong>{{ $count }}</strong>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Action items grouped by faculty (and one dept-wide at the end) --}}
    @php
        $facultyItems = $plan->items->whereNotNull('faculty_profile_id')->sortBy(function ($it) {
            return ['high' => 0, 'medium' => 1, 'low' => 2][$it->priority] ?? 9;
        });
        $deptItems = $plan->items->whereNull('faculty_profile_id');
    @endphp

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
        <div class="px-5 py-3.5 border-b border-gray-200 font-semibold text-gray-900">
            Action items ({{ $plan->items->count() }})
        </div>

        @if($facultyItems->isEmpty() && $deptItems->isEmpty())
            <div class="p-6 text-center text-gray-500 text-sm">
                No items in this plan.
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($facultyItems as $item)
                    @include('departmental-plan.partials.item', ['item' => $item, 'priorityClass' => $priorityClass, 'statusClass' => $statusClass, 'categoryLabel' => $categoryLabel, 'canEdit' => $canEditPlan])
                @endforeach
                @foreach($deptItems as $item)
                    @include('departmental-plan.partials.item', ['item' => $item, 'priorityClass' => $priorityClass, 'statusClass' => $statusClass, 'categoryLabel' => $categoryLabel, 'canEdit' => $canEditPlan])
                @endforeach
            </ul>
        @endif
    </div>

@elseif($canGenerate)
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-8 text-center">
        <h2 class="text-lg font-semibold text-gray-900">No plan for this period yet</h2>
        <p class="text-sm text-gray-500 mt-1 max-w-xl mx-auto">
            Generate a departmental plan from the current evaluation results for
            <strong>{{ $schoolYear }} / {{ $semester }}</strong>. The plan will combine each faculty's performance level
            with the recommendation you submitted (retention / promotion / reassignment) and propose specific actions.
        </p>
        <form method="POST" action="{{ route('departmental-plan.generate') }}" class="mt-5">
            @csrf
            <input type="hidden" name="school_year"   value="{{ $schoolYear }}">
            <input type="hidden" name="semester"      value="{{ $semester }}">
            <input type="hidden" name="department_id" value="{{ $department->id }}">
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2.5 rounded-xl shadow-sm">
                Generate Departmental Plan
            </button>
        </form>
    </div>
@endif
@endsection
