@extends('layouts.app')

@section('title', 'Departments')
@section('page-title', 'Departments')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Departments</h1>
        <p class="text-sm text-gray-500 mt-1">Manage teaching and non-teaching departments.</p>
    </div>
</div>

{{-- Summary Stats + Add Button --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-6">
    <div class="p-5">
        <div class="flex gap-4 items-center mb-4">
            @can('manage-departments')
            <button type="button" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('addDeptModal').style.display='flex'">+ Add Department</button>
            @endcan
            <span class="text-gray-500 text-sm">{{ $departments->total() }} total</span>
        </div>
        <div class="flex gap-3 flex-wrap">
            <div class="flex justify-between items-center py-3 px-5 bg-gray-50 rounded-xl min-w-[200px] gap-6">
                <span class="text-sm text-slate-700">Total Departments</span>
                <strong class="text-slate-900">{{ $departments->total() }}</strong>
            </div>
            <div class="flex justify-between items-center py-3 px-5 bg-gray-50 rounded-xl min-w-[200px] gap-6">
                <span class="text-sm text-slate-700">Active (this page)</span>
                <strong class="text-slate-900">{{ $departments->getCollection()->where('is_active', true)->count() }}</strong>
            </div>
            <div class="flex justify-between items-center py-3 px-5 bg-gray-50 rounded-xl min-w-[200px] gap-6">
                <span class="text-sm text-slate-700">Inactive (this page)</span>
                <strong class="text-slate-900">{{ $departments->getCollection()->where('is_active', false)->count() }}</strong>
            </div>
        </div>
    </div>
</div>

{{-- Departments Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
        <span class="font-bold text-slate-900">All Departments</span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $departments->total() }} total</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Code</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Type</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Faculty</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Students</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Status</th>
                    @can('manage-departments')<th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Actions</th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($departments as $dept)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($departments->currentPage() - 1) * $departments->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $dept->code }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $dept->name }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $dept->department_type === 'teaching' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                            {{ ucfirst($dept->department_type) }}
                        </span>
                    </td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $dept->faculty_count }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $dept->student_count }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if($dept->is_active)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Inactive</span>
                        @endif
                    </td>
                    @can('manage-departments')
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <div class="flex gap-1.5 flex-wrap">
                            {{-- Edit trigger --}}
                            <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition"
                                onclick="openEditDept({{ $dept->id }}, '{{ addslashes($dept->code) }}', '{{ addslashes($dept->name) }}', '{{ $dept->department_type }}')">
                                Edit
                            </button>
                            {{-- Deactivate / Reactivate --}}
                            @if($dept->is_active)
                                <form method="POST" action="{{ route('departments.destroy', $dept->id) }}"
                                      onsubmit="return confirm('Deactivate this department?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">Deactivate</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('departments.reactivate', $dept->id) }}"
                                      onsubmit="return confirm('Reactivate this department?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center gap-2 bg-green-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-green-700 transition">Reactivate</button>
                                </form>
                            @endif
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-gray-500 py-8 px-4">
                        No departments found. Add one using the button above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($departments->hasPages())
    <div class="p-4 flex justify-center">
        {{ $departments->links() }}
    </div>
    @endif
</div>

{{-- Add Department Modal --}}
<div id="addDeptModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[520px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Add Department</h3>
        <form method="POST" action="{{ route('departments.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="code">Department Code</label>
                <input type="text" name="code" id="code" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. BSIT" value="{{ old('code') }}" required maxlength="20">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="name">Department Name</label>
                <input type="text" name="name" id="name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. Information Technology" value="{{ old('name') }}" required maxlength="255">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="department_type">Type</label>
                <select name="department_type" id="department_type" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    <option value="">-- Select Type --</option>
                    <option value="teaching" {{ old('department_type') === 'teaching' ? 'selected' : '' }}>Teaching</option>
                    <option value="non-teaching" {{ old('department_type') === 'non-teaching' ? 'selected' : '' }}>Non-Teaching</option>
                </select>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Add Department</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('addDeptModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editDeptModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[480px] shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Edit Department</h3>
        <form method="POST" id="editDeptForm">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="edit_code">Code</label>
                <input type="text" name="code" id="edit_code" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required maxlength="20">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="edit_name">Name</label>
                <input type="text" name="name" id="edit_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required maxlength="255">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="edit_type">Type</label>
                <select name="department_type" id="edit_type" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    <option value="teaching">Teaching</option>
                    <option value="non-teaching">Non-Teaching</option>
                </select>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="closeEditDept()">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($errors->any() && !old('_method'))
    document.getElementById('addDeptModal').style.display = 'flex';
@endif

document.getElementById('addDeptModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('editDeptModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

function openEditDept(id, code, name, type) {
    document.getElementById('editDeptForm').action = @json(url('departments')) + '/' + id;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    const modal = document.getElementById('editDeptModal');
    modal.style.display = 'flex';
}
function closeEditDept() {
    document.getElementById('editDeptModal').style.display = 'none';
}
</script>
@endpush
