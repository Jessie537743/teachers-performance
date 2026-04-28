@extends('layouts.app')

@section('title', 'Intervention Recommendation Module')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6">

    {{-- Header --}}
    <div class="mb-5">
        <h1 class="text-2xl font-bold text-slate-900">Intervention Recommendation Module</h1>
        <p class="text-sm text-slate-500 mt-1">
            Maps each faculty's predicted performance level to the appropriate HR development program and intervention.
            @if ($departmentScoped) <span class="text-amber-700">Showing your department only.</span> @endif
        </p>
    </div>

    {{-- Info banner --}}
    <div class="rounded-2xl bg-emerald-50 border border-emerald-200 p-4 mb-6 flex items-start gap-3">
        <svg class="w-5 h-5 text-emerald-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm text-emerald-900">
            This module automatically maps the predicted performance level of each faculty member to appropriate HR development programs and interventions.
        </p>
    </div>

    {{-- Period filter + KPI tiles --}}
    <div class="grid lg:grid-cols-4 gap-3 mb-5">
        <form method="GET" action="{{ route('intervention-recommendations.index') }}" class="lg:col-span-2 bg-white rounded-2xl ring-1 ring-slate-200 p-4 flex flex-wrap items-end gap-2">
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">School year</label>
                <select name="school_year" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500/30">
                    <option value="">All</option>
                    @foreach ($periods->pluck('school_year')->unique() as $sy)
                        <option value="{{ $sy }}" @selected($schoolYear === $sy)>{{ $sy }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[140px]">
                <label class="block text-xs font-semibold text-slate-600 mb-1">Semester</label>
                <select name="semester" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500/30">
                    <option value="">All</option>
                    @foreach ($periods->pluck('semester')->unique() as $sm)
                        <option value="{{ $sm }}" @selected($semester === $sm)>{{ $sm }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold px-4 py-2">Apply</button>
        </form>
        <div class="bg-white rounded-2xl ring-1 ring-rose-200 p-4">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">High priority</div>
            <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ $counts['high'] }}</div>
            <div class="text-xs text-slate-500">faculty needing intensive intervention</div>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-amber-200 p-4">
            <div class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Medium priority</div>
            <div class="text-2xl font-bold text-slate-900 mt-0.5">{{ $counts['medium'] }}</div>
            <div class="text-xs text-slate-500">scheduled for skills enhancement</div>
        </div>
    </div>

    {{-- Roster --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-900">Faculty Intervention Recommendations</h2>
            <p class="text-xs text-slate-500 mt-0.5">{{ $counts['total'] }} faculty · sorted by priority severity</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80 text-left text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Faculty Name</th>
                        <th class="px-6 py-3">Department</th>
                        <th class="px-6 py-3 text-right">Predicted GWA</th>
                        <th class="px-6 py-3">Predicted Performance Level</th>
                        <th class="px-6 py-3">Recommended Intervention</th>
                        <th class="px-6 py-3">Priority Level</th>
                        <th class="px-6 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-100 text-sm">
                    @forelse ($recommendations as $r)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-6 py-3.5 font-semibold text-slate-900">{{ $r['faculty_name'] }}</td>
                            <td class="px-6 py-3.5 text-slate-700">{{ $r['department_code'] ?: $r['department'] }}</td>
                            <td class="px-6 py-3.5 text-right font-mono text-slate-900">
                                {{ $r['predicted_gwa'] !== null ? number_format((float) $r['predicted_gwa'], 2) : '—' }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold ring-1 {{ $r['recommendation']['level_class'] }}">
                                    {{ $r['performance_level'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-slate-700">{{ $r['recommendation']['intervention'] }}</td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold ring-1 {{ $r['recommendation']['priority_class'] }}">
                                    {{ $r['recommendation']['priority'] }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right">
                                <button type="button"
                                        data-view-details
                                        data-faculty-id="{{ $r['faculty_id'] }}"
                                        class="inline-flex items-center gap-1 rounded-md bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5">
                                    View Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-6 py-12 text-center text-sm text-slate-500">No faculty data for the selected period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Details Modal --}}
<div id="rec-modal" class="hidden fixed inset-0 z-50 bg-slate-900/50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="bg-blue-600 text-white px-6 py-3 flex items-center justify-between">
            <h2 class="text-base font-bold">Sample Intervention Details</h2>
            <button type="button" onclick="document.getElementById('rec-modal').classList.add('hidden')" class="text-white/80 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 overflow-y-auto flex-1" id="rec-modal-body">
            <div class="text-sm text-slate-500 italic">Loading…</div>
        </div>
        <div class="px-6 py-3 border-t border-slate-100 flex justify-end">
            <button type="button" onclick="document.getElementById('rec-modal').classList.add('hidden')"
                    class="inline-flex items-center rounded-md bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2">Close</button>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('rec-modal');
    const body  = document.getElementById('rec-modal-body');
    const baseUrl = @json(route('intervention-recommendations.index'));
    const params = new URLSearchParams({
        school_year: @json($schoolYear ?? ''),
        semester:    @json($semester ?? ''),
    });

    async function open(facultyId) {
        body.innerHTML = '<div class="text-sm text-slate-500 italic">Loading…</div>';
        modal.classList.remove('hidden');

        try {
            const res = await fetch(baseUrl + '/' + facultyId + '?' + params.toString(), {
                headers: { 'Accept': 'application/json' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const d = await res.json();
            renderDetails(d);
        } catch (err) {
            body.innerHTML = '<div class="text-sm text-rose-700">Could not load details.</div>';
        }
    }

    function renderDetails(d) {
        const r = d.recommendation || {};
        const programs = (r.programs || []).map(p => `<li>${escapeHtml(p)}</li>`).join('');
        const gwa = (d.predicted_gwa != null) ? Number(d.predicted_gwa).toFixed(2) : '—';
        body.innerHTML = `
          <dl class="space-y-3 text-sm">
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Faculty Name:</dt>
              <dd class="col-span-2 font-semibold text-slate-900">${escapeHtml(d.faculty_name || '')}</dd>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Department:</dt>
              <dd class="col-span-2 text-slate-900">${escapeHtml(d.department || '—')}</dd>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Predicted GWA:</dt>
              <dd class="col-span-2 font-mono text-slate-900">${gwa}</dd>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Predicted Performance Level:</dt>
              <dd class="col-span-2">
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold ring-1 ${escapeHtml(r.level_class || 'bg-slate-100 text-slate-700 ring-slate-200')}">${escapeHtml(d.performance_level || '—')}</span>
              </dd>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Recommended Intervention:</dt>
              <dd class="col-span-2 font-semibold text-slate-900">${escapeHtml(r.intervention || '—')}</dd>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Description:</dt>
              <dd class="col-span-2 text-slate-700 leading-relaxed">${escapeHtml(r.description || '')}</dd>
            </div>
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Priority Level:</dt>
              <dd class="col-span-2">
                <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-bold ring-1 ${escapeHtml(r.priority_class || 'bg-slate-100 text-slate-700 ring-slate-200')}">${escapeHtml(r.priority || '—')}</span>
              </dd>
            </div>
            ${programs ? `
            <div class="grid grid-cols-3 gap-3">
              <dt class="text-slate-500 col-span-1">Suggested Programs:</dt>
              <dd class="col-span-2"><ul class="list-disc pl-5 text-slate-700 leading-relaxed space-y-0.5">${programs}</ul></dd>
            </div>` : ''}
          </dl>`;
    }

    function escapeHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }

    document.querySelectorAll('[data-view-details]').forEach(btn => {
        btn.addEventListener('click', () => open(btn.dataset.facultyId));
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) modal.classList.add('hidden');
    });
})();
</script>
@endsection
