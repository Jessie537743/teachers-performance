@extends('layouts.app')

@section('content')
@php
    $monthly = $plan['prices']['monthly'] ?? 0;
    $yearly  = $plan['prices']['yearly']  ?? 0;
    $currentPrice = $tenant->billing_cycle === 'yearly' ? $yearly : $monthly;
    $statusColor = match ($tenant->subscription_status) {
        'active'   => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'grace'    => 'bg-amber-50 text-amber-700 ring-amber-200',
        'canceled' => 'bg-slate-100 text-slate-700 ring-slate-200',
        default    => 'bg-slate-100 text-slate-700 ring-slate-200',
    };
@endphp

<div class="max-w-4xl mx-auto py-8 px-6 space-y-6">
    @if (session('status'))
        <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="rounded-lg bg-rose-50 border border-rose-200 px-4 py-3 text-sm text-rose-800">{{ session('error') }}</div>
    @endif

    <div>
        <h1 class="text-2xl font-bold text-slate-900">Billing &amp; subscription</h1>
        <p class="text-sm text-slate-500 mt-1">Manage your plan, billing cycle, and view invoice history.</p>
    </div>

    {{-- Current plan card --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 p-6">
        <div class="flex items-start justify-between gap-4 mb-4">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Current plan</p>
                <h2 class="text-xl font-bold text-slate-900 mt-1">{{ $plan['name'] }}</h2>
                <p class="text-sm text-slate-500 mt-1">{{ $plan['tagline'] }}</p>
            </div>
            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusColor }}">
                {{ str_replace('_', ' ', $tenant->subscription_status) }}
            </span>
        </div>

        @if ($tenant->subscription_status === 'none')
            <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-700">
                You're on the <strong>Free</strong> plan with no active subscription. Upgrade to unlock more features.
            </div>
        @else
            <dl class="grid sm:grid-cols-3 gap-4 pt-4 border-t border-slate-100">
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Billing cycle</dt>
                    <dd class="text-slate-900 font-semibold mt-1 capitalize">{{ $tenant->billing_cycle }}</dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Amount</dt>
                    <dd class="text-slate-900 font-semibold mt-1">
                        ${{ $currentPrice }}<span class="text-xs text-slate-500 font-normal">/{{ $tenant->billing_cycle === 'yearly' ? 'yr' : 'mo' }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-[11px] uppercase tracking-wider text-slate-500 font-semibold">Next invoice</dt>
                    <dd class="text-slate-900 mt-1 text-sm">
                        @if ($tenant->subscription_status === 'canceled')
                            <span class="text-slate-500">No future charges</span>
                        @else
                            {{ $tenant->next_charge_at?->toFormattedDateString() ?? '—' }}
                        @endif
                    </dd>
                </div>
            </dl>

            @if ($tenant->subscription_status === 'canceled')
                <div class="mt-5 rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    Subscription canceled. You'll have access until <strong>{{ $tenant->current_period_end?->toFormattedDateString() }}</strong>.
                </div>
            @elseif ($tenant->subscription_status === 'grace')
                <div class="mt-5 rounded-lg bg-amber-50 border border-amber-200 p-4 text-sm text-amber-800">
                    Last charge failed. We'll retry on <strong>{{ $tenant->next_charge_at?->toFormattedDateString() }}</strong>. Contact support to update payment.
                </div>
            @elseif (in_array($tenant->subscription_status, ['active'], true))
                <div class="mt-5 flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('billing.cancel') }}" onsubmit="return confirm('Cancel your subscription? You will keep access until the current period ends.');">
                        @csrf
                        <button class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                            Cancel subscription
                        </button>
                    </form>
                </div>
            @endif
        @endif
    </div>

    {{-- Invoice history --}}
    <div class="bg-white rounded-2xl shadow-sm ring-1 ring-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-sm font-semibold text-slate-900">Invoice history</h2>
        </div>
        @if ($subscriptions->isEmpty())
            <div class="p-8 text-center text-sm text-slate-500">No invoices yet.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-6 py-3">Date</th>
                            <th class="px-6 py-3">Description</th>
                            <th class="px-6 py-3">Period</th>
                            <th class="px-6 py-3">Amount</th>
                            <th class="px-6 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach ($subscriptions as $s)
                            <tr class="hover:bg-slate-50/60">
                                <td class="px-6 py-3 text-slate-700">{{ $s->created_at->toFormattedDateString() }}</td>
                                <td class="px-6 py-3 text-slate-700">
                                    {{ config('plans.' . $s->plan . '.name') }} — {{ ucfirst($s->billing_cycle) }}
                                </td>
                                <td class="px-6 py-3 text-slate-500 text-xs">
                                    {{ $s->period_start->toDateString() }} → {{ $s->period_end->toDateString() }}
                                </td>
                                <td class="px-6 py-3 text-slate-900 font-medium">{{ $s->formatted_amount }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1
                                        @class([
                                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $s->status === 'paid',
                                            'bg-rose-50 text-rose-700 ring-rose-200'          => $s->status === 'failed',
                                            'bg-slate-100 text-slate-700 ring-slate-200'      => $s->status === 'refunded',
                                        ])">{{ $s->status }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
