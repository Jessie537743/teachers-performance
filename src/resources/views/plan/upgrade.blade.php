@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto py-12 px-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-10 text-center">
        <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 grid place-items-center mb-5">
            <svg class="w-7 h-7 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 mb-2">This feature isn't on your plan</h1>
        <p class="text-slate-600 mb-1">
            You're on the <strong class="text-slate-900">{{ $currentPlan['name'] ?? 'Free' }}</strong> plan.
        </p>
        @if ($feature)
            <p class="text-sm text-slate-500 mb-6">
                Required capability: <code class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700">{{ $feature }}</code>
            </p>
        @endif

        @if (count($upgrades))
            <div class="grid sm:grid-cols-{{ min(count($upgrades), 2) }} gap-4 mt-6 mb-6 text-left">
                @foreach ($upgrades as $plan)
                    <div class="rounded-xl border border-slate-200 p-5">
                        <div class="flex items-baseline justify-between mb-2">
                            <h3 class="font-semibold text-slate-900">{{ $plan['name'] }}</h3>
                            <span class="text-slate-700 text-sm">
                                @if (is_numeric($plan['price'])) ${{ $plan['price'] }} @else {{ $plan['price'] }} @endif
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mb-4">{{ $plan['tagline'] }}</p>
                        <a href="mailto:sales@platform.test?subject=Upgrade to {{ $plan['name'] }}"
                           class="inline-flex items-center justify-center w-full rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5">
                            Contact sales to upgrade
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

        <a href="{{ route('dashboard') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-slate-900 mt-4">
            &larr; Back to dashboard
        </a>
    </div>
</div>
@endsection
