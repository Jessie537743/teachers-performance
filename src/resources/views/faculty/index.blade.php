@extends('layouts.app')

@php
    use App\Enums\FacultyDepartmentPosition;
@endphp

@section('title', 'Faculty')
@section('page-title', 'Faculty Management')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Faculty</h1>
        <p class="text-sm text-gray-500 mt-1">Manage faculty members, department, and role/position (Teaching: Dean/Head, Program Chair, Faculty, Staff; Non-teaching: Department Head or Staff).</p>
    </div>
</div>

{{-- Summary Stats + Add Button --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md mb-6">
    <div class="p-5">
        <div class="flex gap-4 items-center mb-4">
            @can('manage-faculty')
            <button type="button" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('addFacultyModal').style.display='flex'">+ Add Faculty</button>
            <button type="button" class="inline-flex items-center gap-2 bg-slate-100 text-slate-800 px-4 py-2.5 rounded-xl font-semibold hover:bg-slate-200 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('bulkFacultyUploadModal').classList.remove('hidden'); document.getElementById('bulkFacultyUploadModal').classList.add('flex');">Upload CSV</button>
            @endcan
            <span class="text-gray-500 text-sm">{{ $faculty->total() }} total</span>
        </div>
        <div class="flex gap-3 flex-wrap">
            <div class="flex justify-between items-center py-3 px-5 bg-gray-50 rounded-xl min-w-[200px] gap-6">
                <span class="text-sm text-slate-700">Total Faculty</span>
                <strong class="text-slate-900">{{ $faculty->total() }}</strong>
            </div>
            <div class="flex justify-between items-center py-3 px-5 bg-gray-50 rounded-xl min-w-[200px] gap-6">
                <span class="text-sm text-slate-700">Departments</span>
                <strong class="text-slate-900">{{ $departments->count() }}</strong>
            </div>
        </div>
        <p class="mt-3 text-sm text-gray-500">
            New faculty accounts require a password change on first login.
        </p>
    </div>
</div>

@if(session('faculty_bulk_upload_errors'))
<div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
    <p class="text-sm font-semibold text-amber-900 mb-1">Some rows were skipped during CSV upload:</p>
    <ul class="text-xs text-amber-800 space-y-1 list-disc pl-4">
        @foreach(session('faculty_bulk_upload_errors') as $bulkError)
            <li>{{ $bulkError }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($deansByDepartment->isNotEmpty())
<div class="bg-white border border-amber-200 rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-5 py-3.5 border-b border-amber-100 bg-amber-50/80">
        <h2 class="text-lg font-bold text-slate-900">Dean / Administrator-Head by department</h2>
        <p class="text-sm text-slate-600 mt-1 max-w-3xl">
            Identified from <strong>Role / position</strong> = Dean/Head (teaching) or Administrator/Head (non-teaching). Their forms use the
            <span class="font-semibold">EVALUATION FOR ACADEMIC ADMINISTRATORS</span> criterion set
            (<span class="font-semibold">Dean/Head (teaching dept.)</span> or
            <span class="font-semibold">Administrator/Head (non-teaching dept.)</span> in
            @can('manage-criteria')<a href="{{ route('criteria.index') }}" class="text-blue-700 font-semibold underline">Evaluation Criteria</a>@else Evaluation Criteria @endcan
            ), including sections <span class="font-semibold">A through E</span> as listed below.
        </p>
        @if($deanHeadEvaluationCriteria->isNotEmpty())
        <div class="mt-4 rounded-xl border border-amber-100 bg-white/90 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-wide text-amber-900/90 mb-2">Academic administrator evaluation criteria (A–E)</p>
            <ol class="list-decimal list-inside space-y-1.5 m-0 text-sm text-slate-700">
                @foreach($deanHeadEvaluationCriteria as $criterion)
                    <li class="leading-snug"><span class="font-medium text-slate-900">{{ $criterion->name }}</span></li>
                @endforeach
            </ol>
        </div>
        @endif
    </div>
    <div class="p-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        @foreach($deansByDepartment as $deptName => $heads)
        <div class="rounded-xl border border-gray-100 bg-gray-50/60 p-4">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-500 mb-2">{{ $deptName }}</div>
            <ul class="space-y-2 m-0 list-none p-0">
                @foreach($heads as $headUser)
                <li class="text-sm">
                    <span class="font-semibold text-slate-900">{{ $headUser->name }}</span>
                    <span class="text-gray-500 block text-xs">{{ $headUser->email }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endforeach
    </div>
</div>
@endif

{{-- Faculty list --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex flex-col gap-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="font-bold text-slate-900">All Faculty</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $faculty->total() }} total</span>
        </div>
        <form method="GET" action="{{ route('faculty.index') }}" class="flex flex-col sm:flex-row gap-2.5 w-full sm:w-auto sm:items-end">
            <div class="flex-1 min-w-[180px]">
                <label for="filter_search" class="block text-xs font-semibold text-slate-600 mb-1">Search by name</label>
                <input type="text" name="search" id="filter_search" value="{{ request('search') }}"
                       placeholder="Personnel name…"
                       class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
            </div>
            <div class="min-w-[200px]">
                <label for="filter_department" class="block text-xs font-semibold text-slate-600 mb-1">Department</label>
                <select name="department_id" id="filter_department"
                        class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">All departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) request('department_id') === (string) $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ $dept->department_type === 'teaching' ? 'Teaching' : 'Non-teaching' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2 shrink-0">
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap">Search</button>
                @if(request()->filled('search') || request()->filled('department_id'))
                    <a href="{{ route('faculty.index') }}" class="inline-flex items-center justify-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition whitespace-nowrap">Clear</a>
                @endif
            </div>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[880px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Email</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Role / position</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Status</th>
                    @can('manage-faculty')<th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Actions</th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($faculty as $member)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($faculty->currentPage() - 1) * $faculty->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $member->name }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $member->email }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $member->facultyProfile?->department?->name ?? $member->department?->name ?? '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $member->facultyProfile?->departmentPositionLabel() ?? '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if($member->is_active)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Inactive</span>
                        @endif
                    </td>
                    @can('manage-faculty')
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <div class="flex gap-1.5">
                            <button type="button"
                                class="js-edit-faculty inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition"
                                data-faculty-id="{{ $member->id }}"
                                data-name="{{ e($member->name) }}"
                                data-email="{{ e($member->email) }}"
                                data-department-id="{{ $member->facultyProfile?->department_id ?? $member->department_id ?? '' }}"
                                data-department-position="{{ $member->facultyProfile?->department_position?->value ?? 'faculty' }}">
                                Edit
                            </button>
                            @if($member->is_active)
                                <form method="POST" action="{{ route('faculty.destroy', $member->id) }}"
                                      onsubmit="return confirm('Deactivate this faculty member?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">Deactivate</button>
                                </form>
                            @endif
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-gray-500 py-8 px-4">
                        @if(request()->filled('search') || request()->filled('department_id'))
                            No faculty members match your filters.
                        @else
                            No faculty members found. Add one using the button above.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($faculty->hasPages())
    <div class="p-4 flex justify-center">
        {{ $faculty->links() }}
    </div>
    @endif
</div>

{{-- Add Faculty Modal --}}
<div id="bulkFacultyUploadModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[680px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-2 text-lg font-bold text-slate-900">Bulk Upload Faculty (CSV)</h3>
        <p class="text-sm text-gray-600 mb-4">
            Required columns: <strong>full_name</strong>, <strong>email</strong>, <strong>department</strong>, <strong>role</strong>.
        </p>
        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-4">
            <p class="text-xs text-slate-600 mb-2">Role values accepted:</p>
            <ul class="text-xs text-slate-600 space-y-1 list-disc pl-4">
                <li><code>Dean/Head</code> (teaching department leadership)</li>
                <li><code>Administrator/Head</code> (non-teaching department leadership)</li>
                <li><code>Program Chair</code> or <code>Faculty</code> (teaching departments only)</li>
                <li><code>Staff</code> (teaching or non-teaching departments)</li>
            </ul>
            <a href="{{ route('faculty.bulk-template') }}" class="inline-flex mt-3 text-xs font-semibold text-blue-700 hover:text-blue-800 underline">
                Download CSV template
            </a>
        </div>

        <form method="POST" action="{{ route('faculty.bulk-upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="form_source" value="bulk_faculty_upload">
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="bulk_faculty_csv_file">CSV File</label>
            <input
                type="file"
                name="csv_file"
                id="bulk_faculty_csv_file"
                accept=".csv,text/csv"
                class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                required
            >
            <div class="flex gap-2.5 mt-4">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Upload CSV</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="closeBulkFacultyUploadModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Faculty Modal --}}
<div id="addFacultyModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[520px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Add Faculty Member</h3>
        <form method="POST" action="{{ route('faculty.store') }}">
            @csrf
            <input type="hidden" name="form_source" value="add_faculty">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="name">Full Name</label>
                <input type="text" name="name" id="name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. Juan Dela Cruz" value="{{ old('name') }}" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="faculty@smcc.edu.ph" value="{{ old('email') }}" required>
                <small class="text-gray-500 text-xs mt-1">Default password will be set to the email address.</small>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="department_id">Department</label>
                <select name="department_id" id="department_id" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    <option value="">-- Select Department --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" data-department-type="{{ $dept->department_type }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ $dept->department_type === 'teaching' ? 'Teaching' : 'Non-teaching' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="department_position">Role / position in department</label>
                <select name="department_position" id="department_position" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    @foreach(FacultyDepartmentPosition::cases() as $pos)
                        <option value="{{ $pos->value }}" {{ old('department_position', 'faculty') === $pos->value ? 'selected' : '' }}>{{ $pos->label() }}</option>
                    @endforeach
                </select>
            </div>
            <p class="text-xs text-gray-500 mb-4">Evaluation forms follow the <strong>department type</strong> (Teaching vs Non-teaching). Choosing <strong>Dean / Head</strong> assigns system role <strong>Dean</strong> for teaching departments, while non-teaching departments are treated as <strong>Administrator / Head</strong> with system role <strong>Department Head</strong> (same permission group as Dean in Roles &amp; Permissions).</p>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Add Faculty</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('addFacultyModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Modal --}}
<div id="editFacultyModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[480px] shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Edit Faculty Member</h3>
        <form method="POST" id="editFacultyForm">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="edit_name">Full Name</label>
                <input type="text" name="name" id="edit_fname" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="edit_email">Email</label>
                <input type="email" name="email" id="edit_femail" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="edit_fdept">Department</label>
                <select name="department_id" id="edit_fdept" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    <option value="">-- Select Department --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" data-department-type="{{ $dept->department_type }}">{{ $dept->name }} ({{ $dept->department_type === 'teaching' ? 'Teaching' : 'Non-teaching' }})</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="edit_fposition">Role / position in department</label>
                <select name="department_position" id="edit_fposition" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    @foreach(FacultyDepartmentPosition::cases() as $pos)
                        <option value="{{ $pos->value }}">{{ $pos->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="closeEditFaculty()">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($errors->any() && old('form_source') === 'add_faculty')
    document.getElementById('addFacultyModal').style.display = 'flex';
@endif
@if($errors->any() && old('form_source') === 'bulk_faculty_upload')
    document.getElementById('bulkFacultyUploadModal')?.classList.remove('hidden');
    document.getElementById('bulkFacultyUploadModal')?.classList.add('flex');
@endif

document.getElementById('addFacultyModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('editFacultyModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditFaculty();
});
document.getElementById('bulkFacultyUploadModal')?.addEventListener('click', function(e) {
    if (e.target === this) closeBulkFacultyUploadModal();
});

const facultyPositionOptions = {
    teaching: [
        { value: 'dean_head', label: 'Dean / Head' },
        { value: 'program_chair', label: 'Program Chair' },
        { value: 'faculty', label: 'Faculty' },
        { value: 'staff', label: 'Staff' },
    ],
    'non-teaching': [
        { value: 'dean_head', label: 'Department Head' },
        { value: 'staff', label: 'Staff' },
    ],
};

function departmentTypeFromSelect(selectEl) {
    if (!selectEl) return 'teaching';
    const opt = selectEl.options[selectEl.selectedIndex];
    const type = opt?.dataset?.departmentType;
    return type === 'non-teaching' ? 'non-teaching' : 'teaching';
}

function syncDepartmentPositionOptions(departmentSelectId, positionSelectId, preferredValue = null) {
    const deptEl = document.getElementById(departmentSelectId);
    const posEl = document.getElementById(positionSelectId);
    if (!deptEl || !posEl) return;

    const type = departmentTypeFromSelect(deptEl);
    const options = facultyPositionOptions[type] || facultyPositionOptions.teaching;
    const fallbackValue = type === 'non-teaching' ? 'staff' : 'faculty';
    const wanted = preferredValue && String(preferredValue).length ? String(preferredValue) : posEl.value;
    const finalValue = options.some((o) => o.value === wanted) ? wanted : fallbackValue;

    posEl.innerHTML = options
        .map((o) => `<option value="${o.value}">${o.label}</option>`)
        .join('');
    posEl.value = finalValue;
}

function openEditFaculty(id, name, email, deptId, positionValue) {
    document.getElementById('editFacultyForm').action = @json(url('faculty')) + '/' + id;
    document.getElementById('edit_fname').value = name;
    document.getElementById('edit_femail').value = email;
    const deptEl = document.getElementById('edit_fdept');
    if (deptId != null && deptId !== '') {
        deptEl.value = String(deptId);
        if (deptEl.value !== String(deptId)) {
            deptEl.selectedIndex = 0;
        }
    } else {
        deptEl.selectedIndex = 0;
    }
    const pv = positionValue && String(positionValue).length ? String(positionValue) : 'faculty';
    syncDepartmentPositionOptions('edit_fdept', 'edit_fposition', pv);
    document.getElementById('editFacultyModal').classList.remove('hidden');
    document.getElementById('editFacultyModal').classList.add('flex');
}
function closeEditFaculty() {
    const m = document.getElementById('editFacultyModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
}

function closeBulkFacultyUploadModal() {
    const m = document.getElementById('bulkFacultyUploadModal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
}

document.querySelectorAll('.js-edit-faculty').forEach((btn) => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.facultyId;
        const deptRaw = btn.dataset.departmentId;
        const deptId = deptRaw === '' || deptRaw === undefined ? null : deptRaw;
        openEditFaculty(id, btn.dataset.name || '', btn.dataset.email || '', deptId, btn.dataset.departmentPosition || 'faculty');
    });
});

const addDeptEl = document.getElementById('department_id');
if (addDeptEl) {
    addDeptEl.addEventListener('change', function () {
        syncDepartmentPositionOptions('department_id', 'department_position');
    });
    syncDepartmentPositionOptions('department_id', 'department_position', @json(old('department_position', 'faculty')));
}

const editDeptEl = document.getElementById('edit_fdept');
if (editDeptEl) {
    editDeptEl.addEventListener('change', function () {
        syncDepartmentPositionOptions('edit_fdept', 'edit_fposition');
    });
}
</script>
@endpush
