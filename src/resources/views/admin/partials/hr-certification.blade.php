@php
    $hrSignatory = \App\Models\Signature::activeSignatory();
    $wrapClass = $wrapClass ?? 'lp-cert-block';
    $lineClass = $lineClass ?? 'lp-cert-line';
    $showHeading = $showHeading ?? true;
@endphp
<div class="{{ $wrapClass }}">
    @if($showHeading)
        <p class="text-[11px] font-bold uppercase tracking-[0.15em] text-slate-500 mb-4">Certification</p>
    @endif
    <p class="text-xs font-semibold text-slate-600 mb-3">Certified by:</p>
    @if($hrSignatory && $hrSignatory->signature_path)
        <img src="{{ asset('storage/' . $hrSignatory->signature_path) }}" alt="Signature"
             style="max-height:64px;max-width:240px;object-fit:contain;display:block;margin-bottom:4px;">
    @endif
    <p class="text-sm font-bold text-slate-900 uppercase tracking-wide leading-snug">
        {{ $hrSignatory?->user?->name ?: 'Pending HR signatory' }}
    </p>
    <p class="text-sm font-semibold text-slate-800 mt-1.5">
        {{ $hrSignatory?->title ?: 'Head, Human Resource' }}
    </p>
    <hr class="{{ $lineClass }}" aria-hidden="true">
    <p class="text-[10px] text-slate-500 uppercase tracking-wider">Signature</p>
</div>
