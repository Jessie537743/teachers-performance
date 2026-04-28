{{-- AI-Powered Improvement Suggestions panel — per-comment cards.
     Comments come tagged with polarity from the controller. The panel does
     a lazy AJAX call per visible card; "Regenerate" re-fetches with a new
     variant. Cached server-side so re-views are instant. --}}

@php
    $positive = $comments['positive'] ?? collect();
    $negative = $comments['negative'] ?? collect();
    $hasAny   = $positive->isNotEmpty() || $negative->isNotEmpty();
@endphp

<section class="bg-gradient-to-br from-violet-50 via-white to-blue-50 ring-1 ring-violet-100 rounded-2xl p-5 sm:p-6 mt-8">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div class="flex items-center gap-3">
            <span class="w-9 h-9 rounded-lg bg-violet-100 text-violet-600 grid place-items-center">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
            </span>
            <div>
                <h2 class="text-base font-bold text-slate-900">AI-Powered Improvement Suggestions</h2>
                <p class="text-xs text-slate-500">Per-comment analysis · sentiment summary · suggested actions · root cause</p>
            </div>
        </div>
        <span class="text-[11px] uppercase tracking-wider font-semibold text-slate-500">
            Powered by local NLP
        </span>
    </div>

    @if (! $hasAny)
        <div class="rounded-xl bg-white ring-1 ring-slate-200 p-6 text-sm text-slate-600 text-center">
            No free-text comments found for this faculty in the selected period. Once students, the dean, or peers leave written feedback, AI suggestions will appear here.
        </div>
    @else
        <div class="grid lg:grid-cols-2 gap-5">

            {{-- Negative column --}}
            <div class="rounded-2xl bg-rose-50/60 ring-1 ring-rose-200 p-4 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex w-5 h-5 rounded-full bg-rose-200 text-rose-700 items-center justify-center text-[10px] font-bold">!</span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-widest text-rose-700">Negative Feedback</h3>
                </div>
                @forelse ($negative as $row)
                    <x-ai-suggestion-card
                        :comment="$row['comment']"
                        :source="$row['source']"
                        polarity="negative" />
                @empty
                    <p class="text-xs text-slate-500 italic">No negative comments detected.</p>
                @endforelse
            </div>

            {{-- Positive column --}}
            <div class="rounded-2xl bg-emerald-50/60 ring-1 ring-emerald-200 p-4 space-y-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex w-5 h-5 rounded-full bg-emerald-200 text-emerald-700 items-center justify-center text-[10px] font-bold">&check;</span>
                    <h3 class="text-[11px] font-semibold uppercase tracking-widest text-emerald-700">Positive Feedback</h3>
                </div>
                @forelse ($positive as $row)
                    <x-ai-suggestion-card
                        :comment="$row['comment']"
                        :source="$row['source']"
                        polarity="positive" />
                @empty
                    <p class="text-xs text-slate-500 italic">No positive comments detected.</p>
                @endforelse
            </div>
        </div>
    @endif
</section>

<script>
(function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const endpoint = @json(route('feedback-improvement.analyze'));

    async function fetchSuggestion(card, regenerate = false) {
        const body = card.querySelector('[data-card-body]');
        const btn  = card.querySelector('[data-regen]');
        const comment    = card.dataset.comment;
        const sourceKind = card.dataset.source;

        body.innerHTML = '<div class="text-xs text-slate-500 italic flex items-center gap-2"><span class="inline-block w-3 h-3 border-2 border-slate-300 border-t-slate-700 rounded-full animate-spin"></span> Analyzing…</div>';
        if (btn) btn.disabled = true;

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ comment, source_kind: sourceKind, regenerate }),
            });
            if (! res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            renderResult(body, data);
        } catch (err) {
            body.innerHTML = '<div class="text-xs text-rose-700">Could not analyse this comment. Try again later.</div>';
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    function renderResult(body, data) {
        const actions = (data.suggested_actions || []).map(a => `<li>${escapeHtml(a)}</li>`).join('');
        body.innerHTML = `
            <div class="space-y-3">
                <div>
                    <h4 class="text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                        <span>📌</span> <span>Sentiment Summary</span>
                    </h4>
                    <p class="text-sm italic text-slate-700 leading-relaxed">${escapeHtml(data.summary)}</p>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                        <span class="text-emerald-600">✅</span> <span>Suggested Actions</span>
                    </h4>
                    <ul class="text-sm italic text-slate-700 leading-relaxed list-disc pl-5 space-y-1">${actions}</ul>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 mb-1.5 flex items-center gap-1.5">
                        <span>💡</span> <span>Root Cause</span>
                    </h4>
                    <p class="text-sm italic text-slate-700 leading-relaxed">${escapeHtml(data.root_cause || '')}</p>
                </div>
            </div>`;
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    document.querySelectorAll('[data-ai-card]').forEach(card => {
        // Lazy load when scrolled into view
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) {
                    fetchSuggestion(card, false);
                    io.unobserve(card);
                }
            });
        }, { rootMargin: '200px' });
        io.observe(card);

        card.querySelector('[data-regen]')?.addEventListener('click', () => fetchSuggestion(card, true));
    });
})();
</script>
