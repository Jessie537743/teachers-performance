@props([
    'comment' => '',
    'source' => 'student',
    'polarity' => 'negative',
])

@php
    $cardRing = $polarity === 'negative' ? 'ring-rose-200' : 'ring-emerald-200';
    $sourceLabel = match ($source) {
        'student' => 'Student feedback',
        'dean'    => 'Dean evaluation',
        'peer'    => 'Peer evaluation',
        'self'    => 'Self-evaluation',
        default   => 'Feedback',
    };
@endphp

<article data-ai-card data-comment="{{ $comment }}" data-source="{{ $source }}"
         class="bg-white rounded-xl ring-1 {{ $cardRing }} overflow-hidden">
    <header class="px-4 pt-4 pb-3 border-b border-slate-100">
        <div class="flex items-baseline justify-between gap-3 mb-1">
            <span class="text-[10px] font-semibold uppercase tracking-widest text-slate-500">{{ $sourceLabel }}</span>
            <button type="button" data-regen class="inline-flex items-center gap-1.5 rounded-md bg-violet-600 hover:bg-violet-700 disabled:opacity-50 px-2.5 py-1 text-[11px] font-semibold text-white transition">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                Regenerate
            </button>
        </div>
        <blockquote class="text-sm font-bold italic text-slate-900 leading-snug">"{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::limit($comment, 200)) }}"</blockquote>
    </header>

    <div data-card-body class="px-4 py-4">
        <div class="text-xs text-slate-500 italic">Loading AI suggestion…</div>
    </div>
</article>
