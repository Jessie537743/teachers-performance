@extends('layouts.app')

@section('title', 'AI Intervention Plan')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4 sm:px-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 text-blue-700 ring-1 ring-blue-200 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                    AI Intervention Plan
                </span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $profile->user?->name ?? 'Faculty' }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                @if ($profile->department) {{ $profile->department->name }} — @endif
                {{ $semester }} {{ $schoolYear }}
            </p>
        </div>
        <div class="flex flex-wrap gap-2 shrink-0">
            <form method="POST" action="{{ route('faculty.ai-intervention-plan.generate', ['faculty_profile' => $profile]) }}">
                @csrf
                <input type="hidden" name="school_year" value="{{ $schoolYear }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-600 to-blue-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:from-blue-700 hover:to-blue-900 shadow-md transition"
                        onclick="return confirm('{{ $plan ? 'Regenerate this plan? The current plan will be marked superseded.' : 'Generate an AI plan for this period?' }}');">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ $plan ? 'Regenerate' : 'Generate' }}
                </button>
            </form>
            <a href="{{ route('faculty.intervention-suggestions', ['faculty_profile' => $profile, 'school_year' => $schoolYear, 'semester' => $semester]) }}"
               class="inline-flex items-center gap-1 text-sm text-slate-700 px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50">
                Back to suggestions
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @if (! $plan)
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-12 text-center">
            <div class="mx-auto w-14 h-14 rounded-full bg-blue-50 grid place-items-center text-blue-600 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900 mb-1">No AI plan yet for this period</h2>
            <p class="text-sm text-slate-600 max-w-md mx-auto">Generating a plan analyzes weighted Likert scores, clusters weak items by criterion, and asks the ML model what performance lift the recommended actions would yield.</p>
        </div>
    @else
        @php
            $severityColor = match ($plan->severity) {
                'critical' => 'from-rose-600 to-rose-700 text-white',
                'high'     => 'from-amber-500 to-amber-600 text-white',
                default    => 'from-blue-600 to-blue-700 text-white',
            };
            $statusColor = match ($plan->status) {
                'draft'      => 'bg-slate-100 text-slate-700 ring-slate-200',
                'active'     => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'completed'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'superseded' => 'bg-slate-100 text-slate-500 ring-slate-200',
                default      => 'bg-slate-100 text-slate-700 ring-slate-200',
            };
            $outcome = $plan->expected_outcome ?? [];
        @endphp

        {{-- Severity banner + summary --}}
        <div class="rounded-2xl bg-gradient-to-br {{ $severityColor }} p-6 sm:p-8 mb-6 shadow-md">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <span class="inline-flex items-center rounded-full bg-white/20 ring-1 ring-white/30 px-3 py-1 text-[11px] font-bold uppercase tracking-wider">
                    {{ $plan->severity }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-[11px] font-medium">
                    Generated {{ $plan->created_at->diffForHumans() }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-[11px] font-medium">
                    {{ $plan->model_version }}
                </span>
            </div>
            <p class="text-base leading-relaxed whitespace-pre-line">{{ $plan->summary }}</p>
        </div>

        {{-- Outcome metrics --}}
        @if ($outcome)
            <div class="grid sm:grid-cols-4 gap-3 mb-6">
                <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Current avg</div>
                    <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ number_format((float) ($outcome['current_avg'] ?? 0), 2) }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">{{ $outcome['current_level'] ?? '—' }}</div>
                </div>
                <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Target avg</div>
                    <div class="text-2xl font-bold text-blue-700 mt-0.5">{{ number_format((float) ($outcome['target_avg'] ?? 0), 2) }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">+{{ $outcome['lift_pct'] ?? 0 }}% lift</div>
                </div>
                <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Predicted level</div>
                    <div class="text-2xl font-bold text-emerald-700 mt-0.5">{{ $outcome['predicted_level'] ?? '—' }}</div>
                    <div class="text-xs text-slate-500 mt-0.5">if plan completed</div>
                </div>
                <div class="bg-white rounded-xl ring-1 ring-slate-200 p-4">
                    <div class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">ML confidence</div>
                    @if (! empty($outcome['ml_available']))
                        <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ round(($outcome['ml_confidence'] ?? 0) * 100) }}%</div>
                        <div class="text-xs text-slate-500 mt-0.5">Random Forest</div>
                    @else
                        <div class="text-sm font-semibold text-amber-700 mt-1">Unavailable</div>
                        <div class="text-xs text-slate-500 mt-0.5 leading-tight">Train the model on this period to enable predictions.</div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Status pill + actions --}}
        <div class="flex flex-wrap items-center gap-3 mb-6">
            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusColor }}">
                Plan status: {{ $plan->status }}
            </span>
            @if (in_array($plan->status, ['draft', 'active'], true))
                <form method="POST" action="{{ route('faculty.ai-intervention-plan.status', $plan) }}" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="active">
                    @if ($plan->status === 'draft')
                        <button class="text-xs font-medium text-blue-700 hover:text-blue-900">Activate plan →</button>
                    @endif
                </form>
                <form method="POST" action="{{ route('faculty.ai-intervention-plan.status', $plan) }}" class="inline">
                    @csrf
                    <input type="hidden" name="status" value="completed">
                    <button class="text-xs font-medium text-emerald-700 hover:text-emerald-900" onclick="return confirm('Mark this plan completed?');">Mark completed</button>
                </form>
            @endif
        </div>

        {{-- Action timeline --}}
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-900 flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    30 / 60 / 90-day action plan
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Sequenced by cluster severity. Each action carries the curated intervention text + rationale.</p>
            </div>

            @foreach (['30_day' => '30 days — high priority', '60_day' => '60 days', '90_day' => '90 days'] as $phase => $label)
                @php $items = collect($plan->action_items)->where('phase', $phase); @endphp
                @if ($items->isNotEmpty())
                    <div class="px-6 py-5 border-t border-slate-100 first:border-t-0">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-700 font-bold text-xs">
                                {{ str_replace('_day', '', $phase) }}d
                            </span>
                            <h3 class="text-sm font-semibold text-slate-900">{{ $label }}</h3>
                        </div>

                        <div class="space-y-3 ml-11">
                            @foreach ($items as $item)
                                <div class="rounded-xl bg-slate-50 ring-1 ring-slate-200 p-4">
                                    <div class="flex flex-wrap items-baseline gap-2 mb-2">
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                            @class([
                                                'bg-rose-600 text-white'    => $item['priority'] === 'P0',
                                                'bg-amber-500 text-white'   => $item['priority'] === 'P1',
                                                'bg-slate-500 text-white'   => $item['priority'] === 'P2',
                                            ])">{{ $item['priority'] }}</span>
                                        <span class="font-semibold text-slate-900">{{ $item['criterion'] }}</span>
                                        <span class="text-[11px] text-slate-500">cluster avg {{ number_format((float) $item['cluster_avg'], 2) }} · {{ $item['item_count'] }} item(s)</span>
                                    </div>
                                    <p class="text-sm text-slate-700 leading-relaxed mb-2">{{ $item['recommended_intervention'] }}</p>
                                    <p class="text-xs text-slate-500"><strong>Why:</strong> {{ $item['rationale'] }}</p>
                                    <p class="text-xs text-slate-500 mt-1"><strong>Success metric:</strong> {{ $item['success_metric'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach

            @if (empty($plan->action_items))
                <div class="p-6 text-sm text-slate-500">No action items in this plan. Try regenerating once weak items are present.</div>
            @endif
        </div>

        {{-- Theme clusters detail --}}
        @if ($plan->signal_clusters)
            <div class="bg-white rounded-2xl ring-1 ring-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h2 class="text-sm font-bold text-slate-900">Signal clusters</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Weak items grouped by criterion. Clusters drive the action plan above.</p>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($plan->signal_clusters as $cluster)
                        <details class="group">
                            <summary class="flex items-center justify-between gap-3 px-6 py-3.5 cursor-pointer hover:bg-slate-50/60">
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider
                                        @class([
                                            'bg-rose-600 text-white'    => $cluster['priority'] === 'P0',
                                            'bg-amber-500 text-white'   => $cluster['priority'] === 'P1',
                                            'bg-slate-500 text-white'   => $cluster['priority'] === 'P2',
                                        ])">{{ $cluster['priority'] }}</span>
                                    <span class="font-semibold text-slate-900 truncate">{{ $cluster['criterion'] }}</span>
                                    <span class="text-xs text-slate-500 hidden sm:inline">{{ $cluster['theme'] }}</span>
                                </div>
                                <div class="flex items-center gap-3 text-xs text-slate-500">
                                    <span>avg <strong class="text-slate-900">{{ number_format((float) $cluster['average'], 2) }}</strong></span>
                                    <span>{{ $cluster['item_count'] }} items</span>
                                    <svg class="w-4 h-4 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </summary>
                            <div class="px-6 pb-4">
                                <table class="w-full text-xs">
                                    <thead class="text-[10px] uppercase tracking-wider text-slate-500">
                                        <tr><th class="text-left py-1.5 font-semibold">Likert item</th><th class="text-right">Weighted</th><th class="text-right">Student</th><th class="text-right">Dean</th><th class="text-right">Self</th><th class="text-right">Peer</th></tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100">
                                        @foreach ($cluster['items'] as $item)
                                            <tr>
                                                <td class="py-2 pr-3 text-slate-700">{{ \Illuminate\Support\Str::limit($item['question_text'], 100) ?: 'Question #' . $item['question_id'] }}</td>
                                                <td class="text-right font-mono text-slate-900 font-semibold">{{ number_format((float) $item['weighted_score'], 2) }}</td>
                                                @foreach (['student','dean','self','peer'] as $src)
                                                    <td class="text-right font-mono text-slate-500">
                                                        {{ isset($item['sources'][$src]) && $item['sources'][$src] !== null ? number_format((float) $item['sources'][$src], 2) : '—' }}
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        @endif

        <p class="text-xs text-slate-500 mt-6">
            Plan id #{{ $plan->id }} · model {{ $plan->model_version }} · generated by
            {{ $plan->creator?->name ?? 'system' }} on {{ $plan->created_at->toDayDateTimeString() }}.
        </p>
    @endif
</div>
@endsection
