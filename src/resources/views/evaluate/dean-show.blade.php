@extends('layouts.app')

@php
    use App\Services\EvaluationService;
    $likertLabels = EvaluationService::likertScaleLabels($personnelType ?? 'teaching');
    $ptBanner = $personnelType ?? 'teaching';
    if (EvaluationService::isDeanHeadEvaluateePersonnelType($ptBanner)) {
        $deptTypeLabel = 'Dean/Head (academic administrator): 5 = Excellent, 4 = Above Average, 3 = Average, 2 = Below Average, 1 = Unsatisfactory. Rate from 5 down to 1.';
    } elseif ($ptBanner === 'non-teaching') {
        $deptTypeLabel = 'Non-teaching department: rate each item from 5 (highest) down to 1 (lowest).';
    } else {
        $deptTypeLabel = 'Teaching department: rate each item from 5 (highest) down to 1 (lowest).';
    }

@endphp

@section('title', 'Evaluate ' . $facultyUser->name)
@section('page-title', 'Faculty Evaluation Form')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Evaluating: {{ $facultyUser->name }}</h1>
        <p class="text-sm text-gray-500 mt-1">
            Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
        </p>
    </div>
    <a href="{{ route('evaluate.index') }}" class="bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Back to List</a>
</div>

<div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 mb-4 text-sm text-blue-900">
    {{ $deptTypeLabel }}
</div>

{{-- Faculty Info Card --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="p-5 flex items-center gap-4">
        <div class="w-13 h-13 rounded-full bg-blue-600 text-white grid place-items-center text-xl font-bold shrink-0" style="width:52px;height:52px;">
            {{ strtoupper(substr($facultyUser->name, 0, 1)) }}
        </div>
        <div>
            <div class="text-lg font-bold text-gray-900">{{ $facultyUser->name }}</div>
            <div class="text-gray-400 text-sm">{{ $facultyUser->email }}</div>
        </div>
    </div>
</div>

{{-- Rating scale key (matches colored Likert; labels from evaluatee personnel type) --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5 bg-gradient-to-br from-blue-50 to-green-50">
    <div class="p-5">
        <div class="font-bold text-gray-900 mb-3">Rating Scale —
            @if(EvaluationService::isDeanHeadEvaluateePersonnelType($ptBanner))
                Dean/Head (5 highest … 1 lowest)
            @elseif($ptBanner === 'non-teaching')
                non-teaching department (5 highest … 1 lowest)
            @else
                teaching department (5 highest … 1 lowest)
            @endif
        </div>
        <div class="flex gap-2 flex-wrap items-center">
            @foreach([5,4,3,2,1] as $val)
                @php
                    $circleBg = match($val) {
                        1 => 'bg-red-500',
                        2 => 'bg-orange-500',
                        3 => 'bg-yellow-500',
                        4 => 'bg-blue-500',
                        default => 'bg-green-500',
                    };
                @endphp
                <span class="{{ $circleBg }} text-white w-9 h-9 rounded-full inline-flex items-center justify-center text-sm font-bold shadow-sm shrink-0">{{ $val }}</span>
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
<form method="POST" action="{{ route('evaluate.store') }}" id="evaluationForm" onsubmit="event.preventDefault(); showConfirm('Submit this evaluation? This action cannot be undone.', this, {safe: true, confirmText: 'Submit'})">
    @csrf
    <input type="hidden" name="type" value="dean">
    <input type="hidden" name="faculty_id" value="{{ $profile->id }}">

    @php $evaluatorCommentPlaced = false; @endphp
    @forelse($criteria as $criterion)
    @php
        $onlyRecommendation = $criterion->questions->isNotEmpty()
            && $criterion->questions->every(fn ($q) => ($q->response_type ?? 'likert') === 'dean_recommendation');
        $likertCount = $criterion->questions->filter(fn ($q) => ($q->response_type ?? 'likert') === 'likert')->count();
    @endphp
    @if($onlyRecommendation)
        @continue
    @endif
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
            <div>
                <div class="text-base font-bold text-blue-600">{{ $criterion->name }}</div>
                <div class="text-sm text-gray-400 mt-0.5">{{ $likertCount }} {{ $likertCount === 1 ? 'question' : 'questions' }}</div>
            </div>
        </div>
        <div class="p-5">
            @php
                $hasLikert = $criterion->questions->contains(fn ($q) => ($q->response_type ?? 'likert') === 'likert');
            @endphp
            @if($hasLikert)
            {{-- Rating Scale Header (Likert items only) --}}
            <div class="flex gap-1 mb-3 py-2 border-b border-gray-200">
                <div class="flex-1 text-xs font-semibold text-gray-400">Question</div>
                @foreach([5,4,3,2,1] as $val)
                    <div class="w-16 text-center text-xs font-semibold text-blue-500 leading-tight">{{ $val }}</div>
                @endforeach
            </div>
            @endif

            @foreach($criterion->questions as $question)
                @continue(($question->response_type ?? 'likert') === 'dean_recommendation')
                <div class="flex gap-1 items-center py-2.5 border-b border-gray-50">
                    <div class="flex-1 text-sm leading-relaxed flex gap-2 items-start">
                        <span class="font-semibold text-slate-600 tabular-nums shrink-0 select-none min-w-[1.75rem]">{{ $loop->iteration }}.</span>
                        <span>{{ $question->question_text }}</span>
                    </div>
                    @for($r = 5; $r >= 1; $r--)
                        <div class="w-16 flex justify-center">
                            <label class="cursor-pointer flex justify-center">
                                <input type="radio" name="ratings[{{ $question->id }}]"
                                       value="{{ $r }}"
                                       {{ (int) old('ratings.'.$question->id, '') === $r ? 'checked' : '' }}
                                       required
                                       class="w-4.5 h-4.5 cursor-pointer accent-blue-600" style="width:18px;height:18px;">
                            </label>
                        </div>
                    @endfor
                </div>
            @endforeach
        </div>
    </div>
    @if(! $evaluatorCommentPlaced && str_contains(strtoupper($criterion->name), 'GENERAL OBSERVATION'))
        @php $evaluatorCommentPlaced = true; @endphp
        @include('evaluate.partials.evaluator-comment-section')
    @endif
    @empty
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="p-10 text-center text-gray-400">
            No criteria have been set up for dean evaluations. Please contact the administrator.
        </div>
    </div>
    @endforelse

    @if($criteria->count() > 0)
    @if(EvaluationService::isDeanHeadEvaluateePersonnelType((string) ($personnelType ?? '')))
    @php
        $recommendationQuestion = $deanRecommendationQuestions->first();
        $recommendationInputName = $recommendationQuestion
            ? 'ratings['.$recommendationQuestion->id.']'
            : 'recommendation_choice';
        $oldRecommendation = $recommendationQuestion
            ? (int) old('ratings.'.$recommendationQuestion->id, 0)
            : match (old('recommendation_choice')) {
                'retention' => 1,
                'promotion' => 2,
                'reassignment' => 3,
                default => 0,
            };
    @endphp
    {{-- Academic administrator recommendation — criterion name only; choices below (no duplicate question text) --}}
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="px-5 py-3.5 border-b border-gray-200">
            <div class="text-base font-bold text-blue-600">{{ optional(optional($recommendationQuestion)->criterion)->name ?: 'E. WHAT RECOMMENDATIONS WILL YOU MAKE FOR YOUR ACADEMIC ADMINISTRATOR?' }}</div>
        </div>
        <div class="p-5">
            <div class="flex flex-wrap gap-x-10 gap-y-3 items-center">
                @foreach([
                    1 => 'Retention',
                    2 => 'Promotion',
                    3 => 'Re-assignment',
                ] as $val => $label)
                <label class="inline-flex items-center gap-2.5 cursor-pointer text-sm font-medium text-slate-800 select-none">
                    <input type="radio"
                           name="{{ $recommendationInputName }}"
                           value="{{ $val }}"
                           class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                           {{ $oldRecommendation === $val ? 'checked' : '' }}
                           @if($loop->first) required @endif>
                    <span>{{ $label }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    @if(! $evaluatorCommentPlaced)
        @include('evaluate.partials.evaluator-comment-section')
    @endif

    {{-- Submit --}}
    <div class="flex gap-3 items-center">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm">
            Submit Evaluation
        </button>
        <a href="{{ route('evaluate.index') }}" class="bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Cancel</a>
        <span class="text-gray-400 text-sm">All questions must be answered before submitting.</span>
    </div>
    @endif
</form>
@endsection
