@extends('layouts.app')

@php
    use App\Services\EvaluationService;
@endphp

@section('title', 'Intervention suggestions')
@section('page-title', 'Performance interventions')

@section('content')
<div class="max-w-4xl mx-auto mb-6 animate-slide-up">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Intervention suggestions</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ $profile->user?->name ?? 'Faculty' }}
                @if($profile->department)
                    — {{ $profile->department->name }}
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            @plan('ai_predictions')
                @if ($analysis['overall']['qualifies_intervention'])
                    <form method="POST" action="{{ route('faculty.ai-intervention-plan.generate', ['faculty_profile' => $profile]) }}">
                        @csrf
                        <input type="hidden" name="school_year" value="{{ $schoolYear }}">
                        <input type="hidden" name="semester" value="{{ $semester }}">
                        <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:from-blue-700 hover:to-blue-900 shadow-md transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                            Generate AI plan
                        </button>
                    </form>
                @endif
                <a href="{{ route('faculty.ai-intervention-plan.show', ['faculty_profile' => $profile, 'school_year' => $schoolYear, 'semester' => $semester]) }}"
                   class="inline-flex items-center gap-2 bg-white text-slate-900 border border-slate-300 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-slate-50 transition">
                    View AI plan
                </a>
            @endplan
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
               class="inline-flex items-center justify-center bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition">Back</a>
        </div>
    </div>

    <form method="GET" action="{{ route('faculty.intervention-suggestions', ['faculty_profile' => $profile]) }}" class="mt-5 flex flex-wrap gap-3 items-end">
        @php
            $yearOptions = $periods->pluck('school_year')->unique()->filter()->values();
            if ($yearOptions->isEmpty()) {
                $yearOptions = collect([$schoolYear]);
            }
        @endphp
        <div>
            <label for="sy" class="block text-xs font-semibold text-slate-600 mb-1">School year</label>
            <select name="school_year" id="sy" class="border border-gray-200 rounded-xl px-3 py-2 text-sm min-w-[140px]">
                @foreach($yearOptions as $syOpt)
                    <option value="{{ $syOpt }}" {{ (string) $schoolYear === (string) $syOpt ? 'selected' : '' }}>{{ $syOpt }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="sem" class="block text-xs font-semibold text-slate-600 mb-1">Semester</label>
            <select name="semester" id="sem" class="border border-gray-200 rounded-xl px-3 py-2 text-sm min-w-[120px]">
                @foreach(['1st', '2nd', 'Summer'] as $s)
                    <option value="{{ $s }}" {{ (string) $semester === $s ? 'selected' : '' }}>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Apply</button>
    </form>
</div>

<div class="max-w-4xl mx-auto space-y-5">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5">
        <h2 class="text-sm font-bold text-slate-800 uppercase tracking-wide mb-3">Overall performance (this period)</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            <div class="flex justify-between gap-4 py-2 border-b border-gray-100">
                <dt class="text-gray-500">Weighted composite</dt>
                <dd class="font-semibold text-slate-900">{{ $analysis['overall']['weighted_average'] !== null ? number_format($analysis['overall']['weighted_average'], 2) : '—' }}</dd>
            </div>
            <div class="flex justify-between gap-4 py-2 border-b border-gray-100">
                <dt class="text-gray-500">Performance level</dt>
                <dd>
                    @if($analysis['overall']['performance_level'])
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ EvaluationService::performanceBadgeClass($analysis['overall']['performance_level']) }}">{{ $analysis['overall']['performance_level'] }}</span>
                    @else
                        <span class="text-gray-400">No evaluation data</span>
                    @endif
                </dd>
            </div>
        </dl>
        <p class="text-xs text-slate-600 mt-3 leading-relaxed">
            Interventions are suggested when overall level is in the at-risk band for this personnel type:
            <span class="font-semibold text-slate-800">{{ $tierHint }}</span>
        </p>
    </div>

    <div class="bg-indigo-50 border border-indigo-100 rounded-2xl shadow-sm p-5">
        <h2 class="text-sm font-bold text-indigo-900 uppercase tracking-wide mb-2">Rule-based overall summary</h2>
        <p class="text-sm text-slate-800 whitespace-pre-line leading-relaxed m-0">{{ $analysis['overall_summary'] ?? '' }}</p>
        <p class="text-[11px] text-indigo-800/80 mt-3 mb-0">Generated from scores and linked intervention records (deterministic; not external AI).</p>
    </div>

    @if(! $analysis['overall']['qualifies_intervention'])
        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-700">
            Overall performance for this period is <strong>not</strong> in the Fair/Poor (or equivalent) band, so automatic per-item intervention mapping is not shown. Adjust school year and semester if needed.
        </div>
    @elseif(count($analysis['weak_questions']) === 0)
        <div class="rounded-2xl border border-amber-200 bg-amber-50/80 px-5 py-4 text-sm text-amber-950">
            Overall level qualifies for intervention planning, but no Likert question averages in the at-risk band were found for this period (or detailed answers are not yet stored).
        </div>
    @else
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="px-5 py-3.5 border-b border-gray-200 bg-rose-50/60">
                <h2 class="text-base font-bold text-rose-900">Suggested interventions (lowest items first)</h2>
                <p class="text-xs text-rose-800/90 mt-1">Based on weighted per-question scores (student, dean, self, peer) and criteria linked in <strong>Interventions</strong> maintenance.</p>
            </div>
            <ul class="divide-y divide-gray-100 list-none m-0 p-0">
                @foreach($analysis['weak_questions'] as $item)
                <li class="px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold text-sky-700 mb-0.5">{{ $item['criterion_name'] }}</p>
                            <p class="text-sm text-slate-900 font-medium leading-snug">{{ $item['question_text'] }}</p>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="text-lg font-bold tabular-nums text-slate-900">{{ number_format($item['weighted_question_average'], 2) }}</span>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold ml-1 {{ EvaluationService::performanceBadgeClass($item['performance_level']) }}">{{ $item['performance_level'] }}</span>
                        </div>
                    </div>
                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-gray-500">
                        <span>Student: {{ $item['sources']['student'] !== null ? number_format($item['sources']['student'], 2) : '—' }}</span>
                        <span>Dean: {{ $item['sources']['dean'] !== null ? number_format($item['sources']['dean'], 2) : '—' }}</span>
                        <span>Self: {{ $item['sources']['self'] !== null ? number_format($item['sources']['self'], 2) : '—' }}</span>
                        <span>Peer: {{ $item['sources']['peer'] !== null ? number_format($item['sources']['peer'], 2) : '—' }}</span>
                    </div>
                    @if($item['interventions']->isNotEmpty())
                        @foreach($item['interventions'] as $intervention)
                            <div class="mt-3 rounded-xl border border-gray-100 bg-gray-50/80 px-3 py-2.5 text-sm">
                                @if($intervention->indicator)
                                    <p class="font-semibold text-slate-800">{{ $intervention->indicator }}</p>
                                @endif
                                @if($intervention->meaning_low_score)
                                    <p class="text-slate-600 mt-1"><span class="text-xs font-semibold text-slate-500">Low score may indicate:</span> {{ $intervention->meaning_low_score }}</p>
                                @endif
                                @if($intervention->recommended_intervention)
                                    <p class="text-slate-800 mt-2"><span class="text-xs font-semibold text-blue-700">Suggested intervention:</span> {{ $intervention->recommended_intervention }}</p>
                                @endif
                                @if($intervention->basis)
                                    <p class="text-xs text-gray-500 mt-1">Basis: {{ $intervention->basis }}</p>
                                @endif
                            </div>
                        @endforeach
                    @else
                        <p class="mt-3 text-sm text-amber-800 bg-amber-50/80 border border-amber-100 rounded-lg px-3 py-2">
                            No intervention record is mapped to this question yet. Add one under Criteria / Interventions administration, or plan a custom intervention with HR.
                        </p>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
    @endif

    @plan('ai_predictions')
        @include('admin.partials.ai-improvement-suggestions', ['comments' => $comments ?? ['positive' => collect(), 'negative' => collect()]])
    @endplan
</div>
@endsection
