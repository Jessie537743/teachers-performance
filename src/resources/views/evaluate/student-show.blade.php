@extends('layouts.app')

@php
    $likertLabels = \App\Services\EvaluationService::likertScaleLabels($personnelType ?? 'teaching');
@endphp

@section('title', 'Evaluate ' . $facultyUser->name)
@section('page-title', 'Faculty Evaluation')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Evaluate Faculty</h1>
        <p class="text-sm text-gray-500 mt-1">
            Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
        </p>
    </div>
    <a href="{{ route('dashboard') }}" class="bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Back to Dashboard</a>
</div>

{{-- Faculty & Subject Info --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5 flex items-center gap-4">
            <div class="rounded-full bg-blue-600 text-white grid place-items-center text-xl font-bold shrink-0" style="width:52px;height:52px;">
                {{ strtoupper(substr($facultyUser->name, 0, 1)) }}
            </div>
            <div>
                <div class="text-xs uppercase tracking-wider text-gray-400 mb-0.5">Faculty</div>
                <div class="text-lg font-bold text-gray-900">{{ $facultyUser->name }}</div>
                <div class="text-gray-400 text-sm">{{ $facultyUser->email }}</div>
            </div>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-5">
            <div class="text-xs uppercase tracking-wider text-gray-400 mb-1">Subject</div>
            <div class="text-lg font-bold text-gray-900">{{ $subject->title }}</div>
            <div class="text-gray-400 text-sm">{{ $subject->code }} &bull; {{ $subject->semester }} {{ $subject->school_year }}</div>
        </div>
    </div>
</div>

{{-- Rating Scale Info --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4 bg-gradient-to-br from-blue-50 to-green-50">
    <div class="p-5">
        <div class="font-bold text-gray-900 mb-2.5">Rating Scale —
            @if(\App\Services\EvaluationService::isDeanHeadEvaluateePersonnelType((string) ($personnelType ?? '')))
                Dean/Head: 5 = Excellent, 4 = Above Average, 3 = Average, 2 = Below Average, 1 = Unsatisfactory
            @elseif(($personnelType ?? 'teaching') === 'non-teaching')
                non-teaching department (5 highest … 1 lowest)
            @else
                teaching department (5 highest … 1 lowest)
            @endif
        </div>
        <div class="flex gap-2 flex-wrap">
            @foreach([5,4,3,2,1] as $val)
                @php
                    $pill = match($val) {
                        1 => 'bg-red-500',
                        2 => 'bg-orange-500',
                        3 => 'bg-yellow-500',
                        4 => 'bg-blue-500',
                        default => 'bg-green-500',
                    };
                @endphp
                <span class="{{ $pill }} text-white px-3.5 py-1.5 rounded-full text-xs font-bold">{{ $val }}</span>
            @endforeach
        </div>
        <div class="rounded-xl border border-slate-200/80 bg-white/80 px-4 py-3 mt-4">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-700 mb-2">RATINGS:</div>
            <ul class="m-0 p-0 list-none text-sm text-slate-800 space-y-1">
                @foreach([5,4,3,2,1] as $val)
                    <li><span class="font-semibold tabular-nums text-slate-900">{{ $val }}:</span> {{ $likertLabels[$val] }}</li>
                @endforeach
            </ul>
        </div>
    </div>
</div>

{{-- Evaluation Form --}}
<form method="POST" action="{{ route('evaluate.store') }}" id="evalForm">
    @csrf
    <input type="hidden" name="type" value="student">
    <input type="hidden" name="faculty_id" value="{{ $facultyProfile->id }}">
    <input type="hidden" name="subject_id" value="{{ $subject->id }}">

    @forelse($criteria as $criterion)
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
            <div>
                <div class="text-base font-bold text-blue-600">{{ $criterion->name }}</div>
                <div class="text-sm text-gray-400">{{ $criterion->questions->count() }} questions</div>
            </div>
        </div>
        <div class="p-5">
            {{-- Column Headers --}}
            <div class="grid gap-1 mb-2.5 pb-2 border-b border-gray-200" style="grid-template-columns:1fr repeat(5,56px);">
                <div class="text-xs font-semibold text-gray-400">Question</div>
                @foreach([5,4,3,2,1] as $val)
                    <div class="text-center text-xs font-bold text-blue-500 leading-tight">{{ $val }}</div>
                @endforeach
            </div>

            @foreach($criterion->questions as $question)
            <div class="grid gap-1 items-center py-2.5 border-b border-gray-50" style="grid-template-columns:1fr repeat(5,56px);">
                <div class="text-sm leading-relaxed pr-2 flex gap-2 items-start">
                    <span class="font-semibold text-slate-600 tabular-nums shrink-0 select-none min-w-[1.75rem]">{{ $loop->iteration }}.</span>
                    <span>{{ $question->question_text }}</span>
                </div>
                @for($r = 5; $r >= 1; $r--)
                    <div class="flex justify-center">
                        <input type="radio" name="ratings[{{ $question->id }}]"
                               value="{{ $r }}"
                               required
                               class="cursor-pointer accent-blue-600" style="width:18px;height:18px;">
                    </div>
                @endfor
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-10 text-center text-gray-400">
            No evaluation criteria have been configured. Please contact the administrator.
        </div>
    </div>
    @endforelse

    @if($criteria->count() > 0)
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
            <span class="font-semibold text-gray-900">Comments</span>
        </div>
        <div class="p-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="comment">Additional Comments (optional)</label>
                <textarea name="comment" id="comment"
                          class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                          placeholder="Share any additional feedback..."
                          maxlength="2000">{{ old('comment') }}</textarea>
            </div>
        </div>
    </div>

    <div class="flex gap-3 items-center">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm"
                onclick="return confirm('Submit your evaluation? This cannot be undone.')">
            Submit Evaluation
        </button>
        <a href="{{ route('dashboard') }}" class="bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Cancel</a>
    </div>
    @endif
</form>
@endsection
