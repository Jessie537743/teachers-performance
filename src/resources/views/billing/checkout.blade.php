@extends('layouts.app')

@section('content')
@php
    $monthlyDollars = $monthlyCents !== null ? number_format($monthlyCents / 100, 2) : null;
    $yearlyDollars  = $yearlyCents  !== null ? number_format($yearlyCents  / 100, 2) : null;
    $yearlySavings  = ($monthlyCents !== null && $yearlyCents !== null)
        ? number_format(($monthlyCents * 12 - $yearlyCents) / 100, 0)
        : null;
@endphp

<div class="max-w-2xl mx-auto py-12 px-6">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8">
        <h1 class="text-2xl font-bold text-slate-900 mb-1">Confirm your upgrade</h1>
        <p class="text-sm text-slate-500 mb-6">Switching from <strong class="text-slate-900">{{ $currentPlan['name'] ?? 'Free' }}</strong> to <strong class="text-slate-900">{{ $plan['name'] }}</strong>.</p>

        @if (session('error'))
            <div class="mb-4 rounded-lg bg-rose-50 ring-1 ring-rose-200 px-4 py-3 text-sm text-rose-800">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('billing.checkout.confirm') }}" id="checkout-form">
            @csrf
            <input type="hidden" name="plan" value="{{ $planSlug }}">

            {{-- Cycle picker --}}
            @if ($monthlyCents !== null && $yearlyCents !== null)
                <fieldset class="mb-6">
                    <legend class="text-sm font-semibold text-slate-900 mb-2">Billing cycle</legend>
                    <div class="grid sm:grid-cols-2 gap-3">
                        <label class="cycle-option relative flex flex-col rounded-xl border-2 px-5 py-4 cursor-pointer transition {{ $cycle === 'monthly' ? 'border-blue-600 bg-blue-50/40' : 'border-slate-200 hover:border-slate-300' }}">
                            <input type="radio" name="cycle" value="monthly" class="sr-only peer" data-cents="{{ $monthlyCents }}" @checked($cycle === 'monthly')>
                            <span class="text-sm font-semibold text-slate-900">Monthly</span>
                            <span class="text-2xl font-bold text-slate-900 mt-1">${{ $monthlyDollars }}<span class="text-sm font-medium text-slate-500">/mo</span></span>
                            <span class="text-xs text-slate-500 mt-1">Cancel anytime.</span>
                        </label>
                        <label class="cycle-option relative flex flex-col rounded-xl border-2 px-5 py-4 cursor-pointer transition {{ $cycle === 'yearly' ? 'border-blue-600 bg-blue-50/40' : 'border-slate-200 hover:border-slate-300' }}">
                            <input type="radio" name="cycle" value="yearly" class="sr-only peer" data-cents="{{ $yearlyCents }}" @checked($cycle === 'yearly')>
                            @if ($yearlySavings && $yearlySavings > 0)
                                <span class="absolute -top-2.5 right-4 inline-flex items-center rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wider text-white">Save ${{ $yearlySavings }}</span>
                            @endif
                            <span class="text-sm font-semibold text-slate-900">Yearly</span>
                            <span class="text-2xl font-bold text-slate-900 mt-1">${{ $yearlyDollars }}<span class="text-sm font-medium text-slate-500">/yr</span></span>
                            <span class="text-xs text-slate-500 mt-1">2 months free.</span>
                        </label>
                    </div>
                </fieldset>
            @else
                {{-- Only one cycle has a price; submit it as a hidden value --}}
                <input type="hidden" name="cycle" value="{{ $monthlyCents !== null ? 'monthly' : 'yearly' }}">
            @endif

            {{-- Order summary --}}
            <div class="rounded-xl border border-slate-200 divide-y divide-slate-200 mb-6">
                <div class="px-5 py-3 flex items-center justify-between">
                    <span class="text-sm text-slate-600">Plan</span>
                    <span class="text-sm font-semibold text-slate-900">{{ $plan['name'] }}</span>
                </div>
                <div class="px-5 py-3 flex items-center justify-between">
                    <span class="text-sm text-slate-600">Billing cycle</span>
                    <span class="text-sm font-semibold text-slate-900 capitalize" id="summary-cycle">{{ $cycle }}</span>
                </div>
                <div class="px-5 py-3 flex items-center justify-between">
                    <span class="text-sm text-slate-600">Amount due today</span>
                    <span class="text-base font-bold text-slate-900" id="summary-amount">
                        ${{ $cycle === 'yearly' ? $yearlyDollars : $monthlyDollars }}
                    </span>
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

            <div class="flex items-center gap-3">
                <a href="{{ route('plan.upgrade') }}" class="text-sm font-medium text-slate-600 hover:text-slate-900 px-4 py-2.5">
                    Cancel
                </a>
                <button type="submit"
                        class="ml-auto inline-flex items-center justify-center rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5">
                    Confirm payment & switch to {{ $plan['name'] }}
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const radios = document.querySelectorAll('input[name="cycle"][type="radio"]');
        const cycleLabel = document.getElementById('summary-cycle');
        const amountLabel = document.getElementById('summary-amount');
        if (! radios.length || ! cycleLabel || ! amountLabel) return;

        function fmt(cents) {
            return '$' + (cents / 100).toFixed(2);
        }

        radios.forEach((radio) => {
            radio.addEventListener('change', () => {
                cycleLabel.textContent = radio.value;
                amountLabel.textContent = fmt(parseInt(radio.dataset.cents, 10));
                document.querySelectorAll('.cycle-option').forEach((label) => {
                    const checked = label.querySelector('input').checked;
                    label.classList.toggle('border-blue-600', checked);
                    label.classList.toggle('bg-blue-50/40', checked);
                    label.classList.toggle('border-slate-200', ! checked);
                });
            });
        });
    })();
</script>
@endsection
