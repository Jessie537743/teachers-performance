@extends('layouts.app')

@section('title', 'Evaluation Periods')
@section('page-title', 'Evaluation Periods')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Evaluation Periods</h1>
        <p class="text-sm text-gray-500 mt-1">Control when evaluations are open for faculty, students, and deans.</p>
    </div>
</div>

@php $openPeriod = $periods->firstWhere('is_open', true); @endphp

<div class="flex gap-4 items-center flex-wrap mb-6">
    @can('manage-evaluation-periods')
    <button type="button" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('addPeriodModal').style.display='flex'">+ Add Evaluation Period</button>
    @endcan
    @if($openPeriod)
        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-green-100 text-green-700">Open: {{ $openPeriod->school_year }} &mdash; {{ $openPeriod->semester }} ({{ $openPeriod->start_date }} to {{ $openPeriod->end_date }})</span>
    @else
        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold bg-red-100 text-red-700">No evaluation period is currently open</span>
    @endif
</div>

{{-- Add Period Modal --}}
<div id="addPeriodModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[480px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Add Evaluation Period</h3>
        <form method="POST" action="{{ route('evaluation-periods.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="ep_sy">School Year</label>
                <input type="text" name="school_year" id="ep_sy" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. 2024-2025" value="{{ old('school_year') }}" required maxlength="20">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="ep_sem">Semester</label>
                <select name="semester" id="ep_sem" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    <option value="">-- Select Semester --</option>
                    <option value="1st" {{ old('semester') === '1st' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd" {{ old('semester') === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                    <option value="Summer" {{ old('semester') === 'Summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="ep_start">Start Date</label>
                <input type="date" name="start_date" id="ep_start" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       value="{{ old('start_date') }}" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="ep_end">End Date</label>
                <input type="date" name="end_date" id="ep_end" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       value="{{ old('end_date') }}" required>
            </div>
            <div class="mb-4 flex flex-row items-center gap-2.5">
                <input type="checkbox" name="is_open" id="ep_open" value="1"
                       {{ old('is_open') ? 'checked' : '' }}
                       class="w-[18px] h-[18px] cursor-pointer accent-blue-600">
                <label for="ep_open" class="font-semibold cursor-pointer m-0 text-sm text-slate-700">
                    Open this period immediately (closes any other open period)
                </label>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Create Period</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('addPeriodModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Periods Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
        <span class="font-bold text-slate-900">All Evaluation Periods</span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $periods->count() }} total</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">School Year</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Semester</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Start Date</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">End Date</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Status</th>
                    @can('manage-evaluation-periods')<th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Actions</th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($periods as $index => $period)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $index + 1 }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $period->school_year }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $period->semester }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $period->start_date }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $period->end_date }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if($period->is_open)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Open</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Closed</span>
                        @endif
                    </td>
                    @can('manage-evaluation-periods')
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <div class="flex gap-1.5">
                            <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition"
                                onclick="openEditPeriod({{ $period->id }}, '{{ $period->school_year }}', '{{ $period->semester }}', '{{ $period->start_date }}', '{{ $period->end_date }}', {{ $period->is_open ? 'true' : 'false' }})">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('evaluation-periods.destroy', $period->id) }}"
                                  onsubmit="return confirm('Delete this evaluation period?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">Delete</button>
                            </form>
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-500 py-8 px-4">
                        No evaluation periods found.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

{{-- Edit Period Modal --}}
<div id="editPeriodModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[480px] shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Edit Evaluation Period</h3>
        <form method="POST" id="editPeriodForm">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">School Year</label>
                <input type="text" name="school_year" id="ep_esy" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required maxlength="20">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Semester</label>
                <select name="semester" id="ep_esem" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    <option value="1st">1st Semester</option>
                    <option value="2nd">2nd Semester</option>
                    <option value="Summer">Summer</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Start Date</label>
                <input type="date" name="start_date" id="ep_estart" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">End Date</label>
                <input type="date" name="end_date" id="ep_eend" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
            </div>
            <div class="mb-4 flex flex-row items-center gap-2.5">
                <input type="checkbox" name="is_open" id="ep_eopen" value="1"
                       class="w-[18px] h-[18px] cursor-pointer accent-blue-600">
                <label for="ep_eopen" class="font-semibold cursor-pointer m-0 text-sm text-slate-700">Open this period</label>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('editPeriodModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($errors->any() && !old('_method'))
    document.getElementById('addPeriodModal').style.display = 'flex';
@endif

document.getElementById('addPeriodModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('editPeriodModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

function openEditPeriod(id, sy, sem, start, end, isOpen) {
    document.getElementById('editPeriodForm').action = @json(url('evaluation-periods')) + '/' + id;
    document.getElementById('ep_esy').value = sy;
    document.getElementById('ep_esem').value = sem;
    document.getElementById('ep_estart').value = start;
    document.getElementById('ep_eend').value = end;
    document.getElementById('ep_eopen').checked = isOpen;
    document.getElementById('editPeriodModal').style.display = 'flex';
}
</script>
@endpush
