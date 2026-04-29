@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto py-12 px-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Confirm your upgrade</h1>
        <p class="text-sm text-slate-500 mb-6">Switching from <strong class="text-slate-900">{{ $currentPlan['name'] ?? 'Free' }}</strong> to <strong class="text-slate-900">{{ $plan['name'] }}</strong>.</p>

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-rose-50 ring-1 ring-rose-200 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        {{-- Order summary --}}
        <div class="rounded-xl border border-slate-200 divide-y divide-slate-200 mb-6">
            <div class="px-5 py-3 flex items-center justify-between">
                <span class="text-sm text-slate-600">Plan</span>
                <span class="text-sm font-semibold text-slate-900">{{ $plan['name'] }}</span>
            </div>
            <div class="px-5 py-3 flex items-center justify-between">
                <span class="text-sm text-slate-600">Billing cycle</span>
                <span class="text-sm font-semibold text-slate-900 capitalize">{{ $cycle }}</span>
            </div>
            <div class="px-5 py-3 flex items-center justify-between">
                <span class="text-sm text-slate-600">Amount due today</span>
                <span class="text-base font-bold text-slate-900">${{ number_format($priceCents / 100, 2) }}</span>
            </div>
        </div>

        {{-- What you get --}}
        @if (! empty($plan['features']))
            <div class="mb-6">
                <h2 class="text-sm font-semibold text-slate-900 mb-2">What's included</h2>
                <ul class="text-sm text-slate-600 space-y-1.5">
                    @foreach ($plan['features'] as $feature)
                        <li class="flex items-start gap-2">
                            <svg class="w-4 h-4 mt-0.5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            <span>{{ $feature }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Simulated payment notice --}}
        <div class="rounded-lg bg-amber-50 ring-1 ring-amber-200 px-4 py-3 text-xs text-amber-900 mb-6">
            <strong>Simulated payment.</strong> No real card is charged. The platform records a paid subscription row and switches your plan immediately.
        </div>

        <form method="POST" action="{{ route('billing.checkout.confirm') }}" class="flex items-center gap-3">
            @csrf
            <input type="hidden" name="plan" value="{{ $planSlug }}">
            <input type="hidden" name="cycle" value="{{ $cycle }}">

            <a href="{{ url()->previous() }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 px-4 py-2.5">
                Cancel
            </a>
            <button type="submit"
                    class="ml-auto inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5">
                Pay ${{ number_format($priceCents / 100, 2) }} & switch to {{ $plan['name'] }}
            </button>
        </form>
    </div>
</div>
@endsection
