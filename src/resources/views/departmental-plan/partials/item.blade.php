@php
    $faculty = $item->facultyProfile?->user;
    $source  = $item->source ?? [];
    $isDept  = !$item->faculty_profile_id;
@endphp
<li class="px-5 py-4" x-data="{ open: false }">
    <div class="flex items-start gap-3 flex-wrap">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 flex-wrap">
                <span class="px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wider ring-1 ring-inset {{ $priorityClass[$item->priority] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                    {{ ucfirst($item->priority) }} priority
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wider bg-gray-100 text-gray-700 ring-1 ring-inset ring-gray-200">
                    {{ $categoryLabel[$item->category] ?? $item->category }}
                </span>
                <span class="px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wider ring-1 ring-inset {{ $statusClass[$item->status] ?? 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                    {{ str_replace('_', ' ', $item->status) }}
                </span>
                @if(!$isDept && ($source['performance_level'] ?? null))
                    <span class="text-[11px] text-gray-500">Level: <strong>{{ $source['performance_level'] }}</strong></span>
                @endif
                @if(!$isDept && ($source['weighted_average'] ?? null))
                    <span class="text-[11px] text-gray-500">Weighted Avg: <strong>{{ number_format($source['weighted_average'], 2) }}</strong></span>
                @endif
                @if(!$isDept && ($source['dean_recommendation'] ?? null))
                    <span class="text-[11px] text-blue-700">Your recommendation: <strong>{{ ucfirst($source['dean_recommendation']) }}</strong></span>
                @endif
            </div>
            <div class="mt-1.5 font-semibold text-gray-900">{{ $item->title }}</div>
            <p class="text-sm text-gray-600 mt-1">{{ $item->description }}</p>

            @if(!empty($item->programs))
                <div class="mt-2">
                    <div class="text-[11px] uppercase tracking-wider text-gray-500 mb-1">Suggested programs</div>
                    <ul class="text-sm text-gray-700 list-disc pl-5 space-y-0.5">
                        @foreach($item->programs as $prog)
                            <li>{{ $prog }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($item->notes)
                <div class="mt-2 text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                    <span class="font-semibold text-gray-700">Note:</span> {{ $item->notes }}
                </div>
            @endif
        </div>

        @if($canEdit ?? false)
        <div class="flex flex-col items-end gap-1.5 shrink-0">
            <button type="button" x-on:click="open = !open"
                class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                <span x-show="!open">Update status</span>
                <span x-show="open" x-cloak>Cancel</span>
            </button>
        </div>
        @endif
    </div>

    @if($canEdit ?? false)
    <div x-show="open" x-cloak class="mt-3 border-t border-gray-100 pt-3">
        <form method="POST" action="{{ route('departmental-plan.item.status', $item) }}"
              class="flex items-end gap-2 flex-wrap">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    @foreach(['pending', 'in_progress', 'completed', 'cancelled'] as $s)
                        <option value="{{ $s }}" @selected($item->status === $s)>{{ ucfirst(str_replace('_', ' ', $s)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[16rem]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Note (optional)</label>
                <input type="text" name="notes" value="{{ $item->notes }}" maxlength="2000"
                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="e.g., Scheduled for next term">
            </div>
            <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold shadow-sm">
                Save
            </button>
        </form>
    </div>
    @endif
</li>
