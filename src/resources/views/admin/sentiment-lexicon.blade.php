@extends('layouts.app')
@section('title', 'Sentiment Lexicon')
@section('page-title', 'Sentiment Lexicon')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Sentiment Lexicon</h1>
        <p class="text-sm text-gray-500 mt-1">Manage keywords used for comment sentiment classification (positive, negative, neutral).</p>
    </div>
    <button onclick="document.getElementById('addModal').style.display='flex'"
            class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Add Word
    </button>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3.5 mb-5">
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-blue-100 text-blue-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7V4h16v3"/><path d="M9 20h6"/><path d="M12 4v16"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total</div>
            <div class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</div>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-green-100 text-green-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 00-6 0v4"/><path d="M5 21h14a2 2 0 002-2v-5a2 2 0 00-2-2H5a2 2 0 00-2 2v5a2 2 0 002 2z"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Positive</div>
            <div class="text-2xl font-bold text-green-700">{{ $stats['positive'] }}</div>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-red-100 text-red-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Negative</div>
            <div class="text-2xl font-bold text-red-700">{{ $stats['negative'] }}</div>
        </div>
    </div>
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-4 flex items-center gap-4">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center bg-amber-100 text-amber-600">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="5" y1="12" x2="19" y2="12"/></svg>
        </div>
        <div>
            <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Neutral</div>
            <div class="text-2xl font-bold text-amber-700">{{ $stats['neutral'] }}</div>
        </div>
    </div>
</div>

{{-- Filters --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-5">
    <form method="GET" action="{{ route('sentiment-lexicon.index') }}" class="p-4">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Search word</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Type keyword..."
                       class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div class="min-w-[140px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Polarity</label>
                <select name="polarity" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All polarities</option>
                    <option value="positive" {{ request('polarity') === 'positive' ? 'selected' : '' }}>Positive</option>
                    <option value="negative" {{ request('polarity') === 'negative' ? 'selected' : '' }}>Negative</option>
                    <option value="neutral" {{ request('polarity') === 'neutral' ? 'selected' : '' }}>Neutral</option>
                </select>
            </div>
            <div class="min-w-[120px]">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Language</label>
                <select name="language" class="w-full border border-gray-300 rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All languages</option>
                    @foreach($languages as $lang)
                        <option value="{{ $lang }}" {{ request('language') === $lang ? 'selected' : '' }}>{{ $lang }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-blue-700 transition">Filter</button>
                <a href="{{ route('sentiment-lexicon.index') }}" class="bg-gray-100 text-gray-700 px-4 py-2 rounded-xl text-sm font-semibold hover:bg-gray-200 transition">Clear</a>
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-6 py-3 font-semibold">#</th>
                    <th class="px-6 py-3 font-semibold">Word / Phrase</th>
                    <th class="px-6 py-3 font-semibold">Polarity</th>
                    <th class="px-6 py-3 font-semibold">Language</th>
                    <th class="px-6 py-3 font-semibold">Status</th>
                    <th class="px-6 py-3 font-semibold text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($entries as $i => $entry)
                    <tr class="hover:bg-gray-50 transition">
                        <td class="px-6 py-3 text-gray-500">{{ $entries->firstItem() + $i }}</td>
                        <td class="px-6 py-3 font-medium text-slate-900">{{ $entry->word }}</td>
                        <td class="px-6 py-3">
                            @if($entry->polarity === 'positive')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Positive</span>
                            @elseif($entry->polarity === 'negative')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Negative</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Neutral</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-gray-600">{{ $entry->language ?? '—' }}</td>
                        <td class="px-6 py-3">
                            @if($entry->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-gray-100 text-gray-500">Disabled</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <button onclick="openEditModal({{ $entry->id }}, '{{ addslashes($entry->word) }}', '{{ $entry->polarity }}', '{{ $entry->language ?? '' }}', {{ $entry->is_active ? 'true' : 'false' }})"
                                        class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition">
                                    Edit
                                </button>
                                <form method="POST" action="{{ route('sentiment-lexicon.destroy', $entry) }}" onsubmit="return confirm('Delete &quot;{{ $entry->word }}&quot;?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold bg-red-50 text-red-600 border border-red-200 hover:bg-red-100 transition">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">No entries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($entries->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $entries->links() }}
        </div>
    @endif
</div>

{{-- Add Modal --}}
<div id="addModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center" onclick="if(event.target===this)this.style.display='none'">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[480px] mx-4 animate-slide-up">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-slate-900">Add Word</h3>
            <button onclick="document.getElementById('addModal').style.display='none'" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form method="POST" action="{{ route('sentiment-lexicon.store') }}">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Word / Phrase</label>
                    <input type="text" name="word" required placeholder="e.g. very helpful" class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Polarity</label>
                    <select name="polarity" required class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="positive">Positive</option>
                        <option value="negative">Negative</option>
                        <option value="neutral">Neutral</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Language</label>
                    <select name="language" class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Not specified —</option>
                        <option value="en">English</option>
                        <option value="fil">Filipino</option>
                        <option value="ceb">Cebuano</option>
                        <option value="es">Spanish</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="is_active" checked class="w-[17px] h-[17px] accent-blue-600 cursor-pointer">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="document.getElementById('addModal').style.display='none'" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">Add Word</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center" onclick="if(event.target===this)this.style.display='none'">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-[480px] mx-4 animate-slide-up">
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-bold text-slate-900">Edit Word</h3>
            <button onclick="document.getElementById('editModal').style.display='none'" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
        </div>
        <form id="editForm" method="POST">
            @csrf
            @method('PUT')
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Word / Phrase</label>
                    <input type="text" name="word" id="editWord" required class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Polarity</label>
                    <select name="polarity" id="editPolarity" required class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="positive">Positive</option>
                        <option value="negative">Negative</option>
                        <option value="neutral">Neutral</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Language</label>
                    <select name="language" id="editLanguage" class="w-full border border-gray-300 rounded-xl px-3 py-2.5 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">— Not specified —</option>
                        <option value="en">English</option>
                        <option value="fil">Filipino</option>
                        <option value="ceb">Cebuano</option>
                        <option value="es">Spanish</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                <label class="flex items-center gap-2.5 cursor-pointer">
                    <input type="checkbox" name="is_active" id="editActive" class="w-[17px] h-[17px] accent-blue-600 cursor-pointer">
                    <span class="text-sm text-gray-700">Active</span>
                </label>
            </div>
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-gray-200 bg-gray-50 rounded-b-2xl">
                <button type="button" onclick="document.getElementById('editModal').style.display='none'" class="px-4 py-2 rounded-xl text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition">Cancel</button>
                <button type="submit" class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openEditModal(id, word, polarity, language, isActive) {
    document.getElementById('editForm').action = '/sentiment-lexicon/' + id;
    document.getElementById('editWord').value = word;
    document.getElementById('editPolarity').value = polarity;
    document.getElementById('editLanguage').value = language;
    document.getElementById('editActive').checked = isActive;
    document.getElementById('editModal').style.display = 'flex';
}
</script>
@endpush
