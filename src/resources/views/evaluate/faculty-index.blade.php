@extends('layouts.app')

@section('title', 'My Evaluations')
@section('page-title', 'My Evaluations')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Evaluations</h1>
        <p class="text-sm text-gray-500 mt-1">
            @if($period)
                Period: {{ $period->school_year }} &mdash; {{ $period->semester }}
                &nbsp;<span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">Open</span>
            @else
                No evaluation period is currently open.
            @endif
        </p>
    </div>
</div>

@if(!$period)
    <div class="rounded-xl border border-amber-300 bg-amber-50 text-amber-800 px-4 py-3 mb-4">
        Evaluations are currently closed. No actions are available at this time.
    </div>
@endif

{{-- Self Evaluation Section --}}
@can('submit-self-evaluation')
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
        <span class="font-semibold text-gray-900">Self Evaluation</span>
    </div>
    <div class="p-5">
        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl border border-gray-200">
            <div class="flex items-center gap-3.5">
                <div class="w-12 h-12 rounded-full bg-blue-600 text-white grid place-items-center text-lg font-bold shrink-0">
                    {{ strtoupper(substr($faculty->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-bold text-gray-900">{{ $faculty->name }}</div>
                    <div class="text-gray-400 text-sm">Self Assessment</div>
                </div>
            </div>
            <div>
                @if($hasSelfEvaluated)
                    <span class="bg-green-100 text-green-700 px-4 py-2 rounded-full text-sm font-bold">Completed</span>
                @elseif($period)
                    <a href="{{ route('evaluate.show', ['type' => 'self', 'facultyId' => $faculty->id]) }}"
                       class="bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm">
                        Start Self Evaluation
                    </a>
                @else
                    <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">Unavailable</span>
                @endif
            </div>
        </div>
    </div>
</div>
@endcan

{{-- Peer Evaluations Section --}}
@can('submit-peer-evaluation')
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center gap-3">
        <span class="font-semibold text-gray-900">Peer Evaluations</span>
        <span class="bg-blue-100 text-blue-700 px-2.5 py-1 rounded-full text-xs font-bold">{{ $peers->count() }} colleagues</span>
    </div>
    <div class="p-5">
        @if($peers->count() > 0)
            <div class="flex flex-col gap-3">
                @foreach($peers as $peer)
                <div class="flex items-center justify-between p-3.5 bg-gray-50 rounded-xl border border-gray-200">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-500 text-white grid place-items-center font-bold text-base shrink-0">
                            {{ strtoupper(substr($peer['user']->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="font-semibold text-gray-900">{{ $peer['user']->name }}</div>
                            <div class="text-xs text-gray-400">{{ $peer['user']->email }}</div>
                        </div>
                    </div>
                    <div>
                        @if($peer['has_peer_evaluated'])
                            <span class="bg-green-100 text-green-700 px-2.5 py-1 rounded-full text-xs font-bold">Evaluated</span>
                        @elseif($period)
                            <a href="{{ route('evaluate.show', ['type' => 'peer', 'facultyId' => $peer['user']->id]) }}"
                               class="inline-block bg-blue-600 text-white px-3 py-1.5 rounded-xl text-xs font-semibold hover:bg-blue-700 transition shadow-sm">
                                Evaluate
                            </a>
                        @else
                            <span class="bg-amber-100 text-amber-700 px-2.5 py-1 rounded-full text-xs font-bold">Closed</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @else
            <div class="text-center text-gray-400 py-8">
                No colleagues found in your department for peer evaluation.
            </div>
        @endif
    </div>
</div>
@endcan
@endsection
