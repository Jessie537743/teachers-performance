@extends('layouts.app')

@section('title', 'Individual Development Plan')

@push('styles')
<style>
    @media print {
        .idp-no-print { display: none !important; }
        body { background: #fff; }
        .idp-doc { box-shadow: none !important; border: none !important; }
    }
</style>
@endpush

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4 sm:px-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6 idp-no-print">
        <div>
            <div class="inline-flex items-center gap-2 mb-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-violet-50 text-violet-700 ring-1 ring-violet-200 px-3 py-1 text-[11px] font-semibold uppercase tracking-wider">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    Individual Development Plan
                </span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $profile->user?->name ?? 'Faculty' }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                @if ($profile->department) {{ $profile->department->name }} — @endif
                {{ $semester }} {{ $schoolYear }}
            </p>
        </div>

        <div class="flex flex-wrap gap-2 shrink-0">
            <form method="POST" action="{{ route('faculty.idp.generate', ['faculty_profile' => $profile]) }}">
                @csrf
                <input type="hidden" name="school_year" value="{{ $schoolYear }}">
                <input type="hidden" name="semester" value="{{ $semester }}">
                <button type="submit"
                        class="inline-flex items-center gap-2 bg-gradient-to-r from-violet-600 to-violet-800 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:from-violet-700 hover:to-violet-900 shadow-md transition"
                        onclick="return confirm('{{ $plan ? 'Regenerate this IDP? The current plan will be marked superseded.' : 'Generate an IDP for this period?' }}');">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ $plan ? 'Regenerate' : 'Generate' }}
                </button>
            </form>

            @if ($plan)
                <button type="button" onclick="window.print()"
                        class="inline-flex items-center gap-1 text-sm text-slate-700 px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    Print
                </button>
            @endif

            <a href="{{ route('reports.department') }}"
               class="inline-flex items-center gap-1 text-sm text-slate-700 px-4 py-2.5 rounded-xl bg-white border border-slate-300 hover:bg-slate-50">
                Back to department
            </a>
        </div>
    </div>

    {{-- Period selector --}}
    <form method="GET" action="{{ route('faculty.idp.show', ['faculty_profile' => $profile]) }}"
          class="bg-white rounded-xl ring-1 ring-slate-200 p-4 mb-6 idp-no-print">
        <div class="grid sm:grid-cols-3 gap-3 items-end">
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">School year</label>
                <select name="school_year" class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($periods->pluck('school_year')->unique()->values() as $sy)
                        <option value="{{ $sy }}" @selected($sy === $schoolYear)>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">Semester</label>
                <select name="semester" class="w-full rounded-lg border-slate-300 text-sm">
                    @foreach ($periods->pluck('semester')->unique()->values() as $sem)
                        <option value="{{ $sem }}" @selected($sem === $semester)>{{ $sem }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-slate-800 text-white text-sm font-semibold px-4 py-2.5 rounded-lg hover:bg-slate-900">View</button>
        </div>
    </form>

    @if (session('status'))
        <div class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-2.5 text-sm text-emerald-800 idp-no-print">{{ session('status') }}</div>
    @endif

    @if (! $plan)
        {{-- Empty state --}}
        <div class="bg-white rounded-2xl ring-1 ring-slate-200 p-12 text-center idp-doc">
            <div class="mx-auto w-14 h-14 rounded-full bg-violet-50 grid place-items-center text-violet-600 mb-4">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            </div>
            <h2 class="text-lg font-semibold text-slate-900 mb-1">No IDP yet for this period</h2>
            <p class="text-sm text-slate-600 max-w-md mx-auto">
                Generating an IDP analyzes evaluation results across student, dean, self, and peer sources, identifies strengths and growth areas, and produces SMART goals with a 30/60/90-day action plan.
            </p>
            <p class="text-xs text-slate-400 mt-4">Engine: <span class="font-mono">{{ $engine }}</span></p>
        </div>
    @else
        @php
            $statusColor = match ($plan->status) {
                'draft'      => 'bg-slate-100 text-slate-700 ring-slate-200',
                'active'     => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'completed'  => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'superseded' => 'bg-slate-100 text-slate-500 ring-slate-200',
                default      => 'bg-slate-100 text-slate-700 ring-slate-200',
            };
            $generatedFrom = $plan->generated_from ?? [];
        @endphp

        {{-- Summary --}}
        <div class="rounded-2xl bg-gradient-to-br from-violet-700 to-indigo-800 text-white p-6 sm:p-8 mb-6 shadow-md idp-doc">
            <div class="flex flex-wrap items-center gap-3 mb-3">
                <span class="inline-flex items-center rounded-full bg-white/20 ring-1 ring-white/30 px-3 py-1 text-[11px] font-bold uppercase tracking-wider">
                    {{ $plan->engine }}
                </span>
                <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-[11px] font-medium">
                    Generated {{ $plan->created_at->diffForHumans() }}
                </span>
                @if (!empty($generatedFrom['overall_avg']))
                    <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 text-[11px] font-medium">
                        Overall {{ number_format((float) $generatedFrom['overall_avg'], 2) }} · {{ $generatedFrom['overall_level'] ?? '—' }}
                    </span>
                @endif
            </div>
            <p class="text-base leading-relaxed whitespace-pre-line">{{ $plan->summary }}</p>
        </div>

        {{-- Status pill + actions --}}
        <div class="flex flex-wrap items-center gap-3 mb-6 idp-no-print">
            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusColor }}">
                Plan status: {{ $plan->status }}
            </span>

            @if ($plan->status !== 'completed' && $plan->status !== 'superseded')
                <form method="POST" action="{{ route('faculty.idp.status', ['plan' => $plan->id]) }}" class="inline-flex">
                    @csrf
                    <input type="hidden" name="status" value="active">
                    <button class="text-xs font-semibold text-emerald-700 hover:underline">Mark active</button>
                </form>
                <form method="POST" action="{{ route('faculty.idp.status', ['plan' => $plan->id]) }}" class="inline-flex">
                    @csrf
                    <input type="hidden" name="status" value="completed">
                    <button class="text-xs font-semibold text-blue-700 hover:underline">Mark completed</button>
                </form>
            @endif
        </div>

        {{-- Strengths --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 mb-6 idp-doc">
            <h2 class="text-sm font-bold uppercase tracking-wider text-emerald-700 mb-4">Strengths</h2>
            @if (empty($plan->strengths))
                <p class="text-sm text-slate-500">No criterion crossed the strength threshold this cycle.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($plan->strengths as $s)
                        <li class="border-l-4 border-emerald-400 pl-4">
                            <div class="font-semibold text-slate-900">{{ $s['area'] ?? '—' }}
                                @if (isset($s['score']))
                                    <span class="ml-2 text-xs font-medium text-emerald-700">{{ number_format((float) $s['score'], 2) }}</span>
                                @endif
                            </div>
                            @if (!empty($s['evidence']))
                                <div class="text-xs text-slate-500 mt-1">{{ $s['evidence'] }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- Growth Areas --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 mb-6 idp-doc">
            <h2 class="text-sm font-bold uppercase tracking-wider text-amber-700 mb-4">Growth Areas</h2>
            @if (empty($plan->growth_areas))
                <p class="text-sm text-slate-500">All rubric dimensions are at or above the development threshold this cycle.</p>
            @else
                <ul class="space-y-3">
                    @foreach ($plan->growth_areas as $g)
                        <li class="border-l-4 border-amber-400 pl-4">
                            <div class="font-semibold text-slate-900">{{ $g['area'] ?? '—' }}</div>
                            <div class="text-xs text-slate-600 mt-1">
                                Current: <span class="font-medium">{{ $g['current_level'] ?? '—' }}</span>
                                <span class="mx-1 text-slate-400">→</span>
                                Target: <span class="font-medium text-amber-700">{{ $g['target_level'] ?? '—' }}</span>
                                @if (isset($g['gap']))
                                    <span class="ml-2 text-slate-400">(+{{ number_format((float) $g['gap'], 2) }})</span>
                                @endif
                            </div>
                            @if (!empty($g['evidence']))
                                <div class="text-xs text-slate-500 mt-1">{{ $g['evidence'] }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- SMART Goals --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 mb-6 idp-doc">
            <h2 class="text-sm font-bold uppercase tracking-wider text-violet-700 mb-4">SMART Goals</h2>
            @if (empty($plan->goals))
                <p class="text-sm text-slate-500">No specific goals — this cycle is maintenance-focused.</p>
            @else
                <div class="space-y-5">
                    @foreach ($plan->goals as $i => $goal)
                        <div class="rounded-xl bg-violet-50/50 ring-1 ring-violet-100 p-4">
                            <div class="font-semibold text-slate-900 mb-2">{{ $i + 1 }}. {{ $goal['title'] ?? '—' }}</div>
                            <dl class="grid sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                                @foreach (['specific' => 'Specific', 'measurable' => 'Measurable', 'achievable' => 'Achievable', 'relevant' => 'Relevant', 'time_bound' => 'Time-bound'] as $key => $label)
                                    @if (!empty($goal[$key]))
                                        <div>
                                            <dt class="text-[10px] font-bold uppercase tracking-wider text-violet-700">{{ $label }}</dt>
                                            <dd class="text-slate-700 mt-0.5">{{ $goal[$key] }}</dd>
                                        </div>
                                    @endif
                                @endforeach
                            </dl>
                            @if (!empty($goal['target_date']))
                                <div class="mt-3 text-xs text-slate-500">Target date: <span class="font-mono">{{ $goal['target_date'] }}</span></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        {{-- Action Items --}}
        <section class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 mb-6 idp-doc">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4">Action Plan (30 / 60 / 90 days)</h2>
            @if (empty($plan->action_items))
                <p class="text-sm text-slate-500">—</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-[11px] font-bold uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <th class="text-left py-2 pr-3">Phase</th>
                                <th class="text-left py-2 pr-3">Action</th>
                                <th class="text-left py-2 pr-3">Resources</th>
                                <th class="text-left py-2 pr-3">Owner</th>
                                <th class="text-left py-2">Due</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($plan->action_items as $a)
                                <tr class="border-b border-slate-100 align-top">
                                    <td class="py-2.5 pr-3 font-semibold text-slate-700 whitespace-nowrap">{{ $a['phase'] ?? '—' }}</td>
                                    <td class="py-2.5 pr-3 text-slate-700">{{ $a['action'] ?? '—' }}</td>
                                    <td class="py-2.5 pr-3 text-slate-500 text-xs">{{ $a['resources'] ?? '—' }}</td>
                                    <td class="py-2.5 pr-3 text-slate-500 text-xs">{{ $a['owner'] ?? '—' }}</td>
                                    <td class="py-2.5 text-slate-500 text-xs font-mono">{{ $a['due'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Expected Outcomes + Resources --}}
        <div class="grid md:grid-cols-2 gap-6">
            <section class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 idp-doc">
                <h2 class="text-sm font-bold uppercase tracking-wider text-blue-700 mb-4">Expected Outcomes</h2>
                @if (empty($plan->expected_outcomes))
                    <p class="text-sm text-slate-500">—</p>
                @else
                    <ul class="space-y-2 text-sm text-slate-700 list-disc list-inside">
                        @foreach ($plan->expected_outcomes as $o)
                            <li>{{ $o }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
            <section class="bg-white rounded-2xl ring-1 ring-slate-200 p-6 idp-doc">
                <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 mb-4">Recommended Resources</h2>
                @if (empty($plan->recommended_resources))
                    <p class="text-sm text-slate-500">—</p>
                @else
                    <ul class="space-y-2 text-sm text-slate-700 list-disc list-inside">
                        @foreach ($plan->recommended_resources as $r)
                            <li>{{ $r }}</li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>
    @endif
</div>
@endsection
