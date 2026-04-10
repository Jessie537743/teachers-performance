@extends('layouts.app')

@php
    use App\Services\EvaluationService;
    $likertLabels = EvaluationService::likertScaleLabels($personnelType ?? 'teaching');
@endphp

@section('title', ($type === 'self' ? 'Self' : 'Peer') . ' Evaluation')
@section('page-title', ($type === 'self' ? 'Self Evaluation' : 'Peer Evaluation'))

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">
            @if($type === 'self')
                Self Evaluation
            @else
                Peer Evaluation: {{ $targetUser->name }}
            @endif
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Period: {{ $period->school_year }} &mdash; {{ format_semester($period->semester) }}
        </p>
    </div>
    <a href="{{ route('evaluate.index') }}" class="bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Back</a>
</div>

{{-- Info Card --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="p-5 flex items-center gap-4">
        <div class="rounded-full text-white grid place-items-center text-xl font-bold shrink-0 {{ $type === 'self' ? 'bg-blue-600' : 'bg-slate-500' }}" style="width:52px;height:52px;">
            {{ strtoupper(substr($targetUser->name, 0, 1)) }}
        </div>
        <div>
            <div class="text-xs uppercase tracking-wider text-gray-400">
                {{ $type === 'self' ? 'Self Assessment' : 'Peer Being Evaluated' }}
            </div>
            <div class="text-lg font-bold text-gray-900">{{ $targetUser->name }}</div>
            <div class="text-gray-400 text-sm">{{ $targetUser->email }}</div>
        </div>
        <span class="ml-auto {{ $type === 'self' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }} px-4 py-2 rounded-full text-xs font-bold">
            {{ strtoupper($type) }} Evaluation
        </span>
    </div>
</div>

{{-- Rating Scale --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4 bg-gradient-to-br from-blue-50 to-green-50">
    <div class="p-5">
        @if(EvaluationService::isDeanHeadEvaluateePersonnelType((string) ($personnelType ?? '')))
            <div class="font-bold text-slate-900 mb-3 text-base">Rating Scale — Dean/Head: 5 highest … 1 lowest</div>
            <div class="flex gap-2 flex-wrap items-center mb-4">
                @foreach([5,4,3,2,1] as $val)
                    @php
                        $circleBg = match($val) {
                            1 => 'bg-red-500',
                            2 => 'bg-orange-500',
                            3 => 'bg-amber-400',
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
        @else
            <div class="font-bold text-gray-900 mb-2.5">Rating Scale —
                @if(($personnelType ?? 'teaching') === 'non-teaching')
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
        @endif
    </div>
</div>

{{-- Evaluation Form --}}
<form method="POST" action="{{ route('evaluate.store') }}">
    @csrf
    <input type="hidden" name="evaluatee_faculty_id" value="{{ $targetProfile->id }}">
    <input type="hidden" name="type" value="{{ $type }}">

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
                <div class="text-sm text-gray-400">{{ $likertCount }} {{ $likertCount === 1 ? 'question' : 'questions' }}</div>
            </div>
        </div>
        <div class="p-5">
            @php
                $hasLikert = $criterion->questions->contains(fn ($q) => ($q->response_type ?? 'likert') === 'likert');
            @endphp
            @if($hasLikert)
            <div class="grid gap-1 mb-2.5 pb-2 border-b border-gray-200" style="grid-template-columns:1fr repeat(5,56px);">
                <div class="text-xs font-semibold text-gray-400">Question</div>
                @foreach([5,4,3,2,1] as $val)
                    <div class="text-center text-xs font-bold text-blue-500 leading-tight">{{ $val }}</div>
                @endforeach
            </div>
            @endif

            @php $likertNum = 0; @endphp
            @foreach($criterion->questions as $question)
                @continue(($question->response_type ?? 'likert') === 'dean_recommendation')
                @php $likertNum++; @endphp
            <div class="grid gap-1 items-center py-2.5 border-b border-gray-50" style="grid-template-columns:1fr repeat(5,56px);">
                <div class="text-sm leading-relaxed pr-2 flex gap-2 items-start">
                    <span class="font-semibold text-slate-600 tabular-nums shrink-0 select-none min-w-[1.75rem]">{{ $likertNum }}.</span>
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
            No criteria configured for {{ $type }} evaluations. Contact the administrator.
        </div>
    </div>
    @endforelse

    @if($criteria->count() > 0)
    @if($deanRecommendationQuestions->isNotEmpty())
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="p-5 space-y-6">
            @foreach($deanRecommendationQuestions as $question)
            @php
                $oldVal = (int) old('ratings.'.$question->id, 0);
            @endphp
            <div class="border-b border-gray-100 pb-6 last:border-b-0 last:pb-0">
                <div class="text-base font-bold text-sky-600 uppercase tracking-wide mb-5 leading-snug">
                    E. WHAT RECOMMENDATIONS WILL YOU MAKE FOR YOUR ACADEMIC ADMINISTRATOR?
                </div>
                @if(filled($question->question_text) && $question->question_text !== 'Select one recommendation below.')
                    <p class="text-sm text-gray-600 mb-4">{{ $question->question_text }}</p>
                @endif
                <div class="flex flex-wrap gap-x-10 gap-y-3 items-center">
                    @foreach([
                        1 => 'Retention',
                        2 => 'Promotion',
                        3 => 'Re-assignment',
                    ] as $val => $label)
                    <label class="inline-flex items-center gap-2.5 cursor-pointer text-sm font-medium text-slate-800 select-none">
                        <input type="radio"
                               name="ratings[{{ $question->id }}]"
                               value="{{ $val }}"
                               class="h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500"
                               {{ $oldVal === $val ? 'checked' : '' }}
                               @if($loop->first) required @endif>
                        <span>{{ $label }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    @if($type === 'peer')
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
            <span class="font-semibold text-gray-900">Comments</span>
        </div>
        <div class="p-5">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5" for="comment">Additional Comments (optional)</label>
                <textarea name="comment" id="comment"
                          class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                          placeholder="Provide constructive feedback for your colleague..."
                          maxlength="2000">{{ old('comment') }}</textarea>
            </div>
        </div>
    </div>
    @endif

    @if($type === 'self' && EvaluationService::isDeanHeadEvaluateePersonnelType((string) ($personnelType ?? '')))
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-4">
        <div class="p-5">
            <label for="self_evaluation_comment" class="block text-sm font-medium text-gray-600 mb-2">F. Please write your comments here.</label>
            <textarea name="comment" id="self_evaluation_comment"
                      class="w-full min-h-[140px] rounded-xl border border-gray-300 px-4 py-3 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                      placeholder=""
                      maxlength="2000">{{ old('comment') }}</textarea>
        </div>
    </div>
    @endif

    <div class="flex gap-3 items-center">
        <button type="submit" class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm"
                onclick="return confirm('Submit this evaluation? This cannot be undone.')">
            Submit Evaluation
        </button>
        <a href="{{ route('evaluate.index') }}" class="bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">Cancel</a>
    </div>
    @endif
</form>
@endsection
