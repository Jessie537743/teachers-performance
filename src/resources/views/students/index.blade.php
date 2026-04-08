@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Student Management')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Students</h1>
        <p class="text-sm text-gray-500 mt-1">Manage student accounts.</p>
    </div>
</div>

{{-- Add Button + Count --}}
<div class="flex gap-4 items-center mb-6">
    @can('manage-students')
    <button type="button" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('addStudentModal').classList.remove('hidden')">+ Add Student</button>
    <button type="button" class="inline-flex items-center gap-2 bg-slate-100 text-slate-800 px-4 py-2.5 rounded-xl font-semibold hover:bg-slate-200 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('bulkUploadModal').classList.remove('hidden')">Upload CSV</button>
    @endcan
    <span class="text-gray-500 text-sm">{{ $students->total() }} total</span>
</div>

<form method="GET" action="{{ route('students.index') }}" class="mb-5">
    <div class="flex items-center gap-2">
        <input
            type="text"
            name="search"
            value="{{ $search ?? '' }}"
            placeholder="Search student name..."
            class="w-full max-w-md border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
        >
        <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-sm">
            Search
        </button>
        @if(!empty($search))
            <a href="{{ route('students.index') }}" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                Clear
            </a>
        @endif
    </div>
</form>

@if(session('bulk_upload_errors'))
    <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
        <p class="text-sm font-semibold text-amber-900 mb-1">Some rows were skipped during bulk upload:</p>
        <ul class="text-xs text-amber-800 space-y-1 list-disc pl-4">
            @foreach(session('bulk_upload_errors') as $bulkError)
                <li>{{ $bulkError }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Students list --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
        <span class="font-bold text-slate-900">All Students</span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $students->total() }} total</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Student ID</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Course</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Year</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Section</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Status</th>
                    @can('manage-students')<th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Actions</th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                @php
                    $rawSection = trim((string) ($student->studentProfile?->section ?? ''));
                    $displaySection = preg_replace('/^section\s*/i', '', $rawSection) ?? $rawSection;
                    if (preg_match('/^[A-Za-z]$/', $displaySection)) {
                        $displaySection = (string) (ord(strtoupper($displaySection)) - ord('A') + 1);
                    }
                @endphp
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($students->currentPage() - 1) * $students->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $student->name }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $student->studentProfile?->student_id ?? '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $student->studentProfile?->department?->name ?? '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $student->studentProfile?->course ?? '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $student->studentProfile?->year_level ? 'Year ' . $student->studentProfile->year_level : '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $displaySection !== '' ? $displaySection : '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <div class="flex flex-col gap-1">
                            @if($student->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700 w-fit">Active</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700 w-fit">Inactive</span>
                            @endif
                            @if($student->studentProfile?->student_status)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 w-fit capitalize">{{ $student->studentProfile->student_status }}</span>
                            @endif
                        </div>
                    </td>
                    @can('manage-students')
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <div class="flex gap-1.5 flex-wrap">
                            <button type="button"
                                class="open-edit-student-btn inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition"
                                data-id="{{ $student->id }}"
                                data-name="{{ e($student->name) }}"
                                data-student-id="{{ e($student->studentProfile?->student_id ?? '') }}"
                                data-email="{{ e($student->email) }}"
                                data-dept-id="{{ $student->department_id ?? '' }}"
                                data-course="{{ e($student->studentProfile?->course ?? '') }}"
                                data-year="{{ (int) ($student->studentProfile?->year_level ?? 1) }}"
                                data-section="{{ e($displaySection) }}"
                                data-semester="{{ e($student->studentProfile?->semester ?? '') }}"
                                data-school-year="{{ e($student->studentProfile?->school_year ?? '') }}"
                                data-status="{{ e($student->studentProfile?->student_status ?? 'regular') }}">
                                Edit
                            </button>
                            @if($student->is_active)
                                <form method="POST" action="{{ route('students.destroy', $student->id) }}"
                                      onsubmit="return confirm('Deactivate this student?')">
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
                    <td colspan="9" class="text-center text-gray-500 py-8 px-4">
                        No students found. Add one using the button above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())
    <div class="p-4 flex justify-center">
        {{ $students->links() }}
    </div>
    @endif
</div>

{{-- Bulk Upload Modal --}}
<div id="bulkUploadModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] flex items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[640px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-2 text-lg font-bold text-slate-900">Bulk Upload Students (CSV)</h3>
        <p class="text-sm text-gray-600 mb-4">
            Required columns: <strong>name</strong>, <strong>student_id</strong>, <strong>department</strong>, <strong>course</strong>, <strong>year_level</strong>, <strong>section</strong>, <strong>student_status</strong>.
            Optional column: <strong>email</strong>.
        </p>
        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-4">
            <p class="text-xs text-slate-600 mb-2">Notes:</p>
            <ul class="text-xs text-slate-600 space-y-1 list-disc pl-4">
                <li><code>student_status</code> values: <code>regular</code> or <code>irregular</code>.</li>
                <li>Semester and School Year are assigned automatically by the system.</li>
            </ul>
            <a href="{{ route('students.bulk-template') }}" class="inline-flex mt-3 text-xs font-semibold text-blue-700 hover:text-blue-800 underline">
                Download CSV template
            </a>
        </div>

        <form method="POST" action="{{ route('students.bulk-upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="form_source" value="bulk_upload">
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="bulk_csv_file">CSV File</label>
            <input
                type="file"
                name="csv_file"
                id="bulk_csv_file"
                accept=".csv,text/csv"
                class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                required
            >
            <div class="flex gap-2.5 mt-4">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Upload CSV</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('bulkUploadModal').classList.add('hidden')">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Add Student Modal --}}
<div id="addStudentModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] flex items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[580px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Add Student</h3>
        <form method="POST" action="{{ route('students.store') }}">
            @csrf
            <input type="hidden" name="form_source" value="add_student">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_name">Full Name</label>
                    <input type="text" name="name" id="s_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="e.g. Maria Santos" value="{{ old('name') }}" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_student_id">Student ID</label>
                    <input type="text" name="student_id" id="s_student_id" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="e.g. 2024-00001" value="{{ old('student_id') }}" required maxlength="50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_email">Email (Optional)</label>
                    <input type="email" name="email" id="s_email" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="student@smcc.edu.ph" value="{{ old('email') }}">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_dept">Department</label>
                    <select name="department_id" id="s_dept" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="">-- Select Department --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_course">Course</label>
                    <input type="text" name="course" id="s_course" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="e.g. BSIT" value="{{ old('course') }}" required maxlength="100">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_year">Year Level</label>
                    <select name="year_level" id="s_year" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="">-- Year --</option>
                        @for($y = 1; $y <= 6; $y++)
                            <option value="{{ $y }}" {{ old('year_level') == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_section">Section</label>
                    <input type="text" name="section" id="s_section" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="e.g. 1" value="{{ old('section') }}" required maxlength="50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="s_status">Student Status</label>
                    <select name="student_status" id="s_status" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="regular" {{ old('student_status', 'regular') === 'regular' ? 'selected' : '' }}>Regular</option>
                        <option value="irregular" {{ old('student_status') === 'irregular' ? 'selected' : '' }}>Irregular</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="semester" id="s_semester" value="{{ old('semester', $defaultSemester) }}">
            <input type="hidden" name="school_year" id="s_sy" value="{{ old('school_year', $defaultSchoolYear) }}">
            <p class="text-xs text-gray-500 mb-3">
                Semester and School Year are automatically set to the current evaluation period.
            </p>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Add Student</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('addStudentModal').classList.add('hidden')">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Student Modal --}}
<div id="editStudentModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] flex items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[580px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Edit Student</h3>
        <form method="POST" id="editStudentForm">
            @csrf @method('PUT')
            <input type="hidden" name="form_source" value="edit_student">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Full Name</label>
                    <input type="text" name="name" id="est_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Student ID</label>
                    <input type="text" name="student_id" id="est_student_id" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required maxlength="50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Email (Optional)</label>
                    <input type="email" name="email" id="est_email" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Department</label>
                    <select name="department_id" id="est_dept" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Course</label>
                    <input type="text" name="course" id="est_course" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Year Level</label>
                    <select name="year_level" id="est_year" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        @for($y = 1; $y <= 6; $y++)
                            <option value="{{ $y }}">Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Section</label>
                    <input type="text" name="section" id="est_section" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="est_status">Student Status</label>
                    <select name="student_status" id="est_status" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="regular">Regular</option>
                        <option value="irregular">Irregular</option>
                    </select>
                </div>
            </div>
            <input type="hidden" name="semester" id="est_semester" value="{{ $defaultSemester }}">
            <input type="hidden" name="school_year" id="est_sy" value="{{ $defaultSchoolYear }}">
            <p class="text-xs text-gray-500 mb-3">
                Semester and School Year are automatically set by the system.
            </p>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('editStudentModal').classList.add('hidden')">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($errors->any() && old('form_source') === 'add_student')
    document.getElementById('addStudentModal')?.classList.remove('hidden');
@endif
@if($errors->any() && old('form_source') === 'bulk_upload')
    document.getElementById('bulkUploadModal')?.classList.remove('hidden');
@endif

document.getElementById('addStudentModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
document.getElementById('editStudentModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});
document.getElementById('bulkUploadModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.classList.add('hidden');
});

const editStudentStatusEl = document.getElementById('est_status');

function openEditStudent(id, name, studentId, email, deptId, course, year, section, semester, sy, studentStatus) {
    const editModal = document.getElementById('editStudentModal');
    document.getElementById('editStudentForm').action = @json(url('students')) + '/' + id;
    document.getElementById('est_name').value = name;
    document.getElementById('est_student_id').value = studentId || '';
    document.getElementById('est_email').value = email;
    if (deptId != null && deptId !== '') document.getElementById('est_dept').value = String(deptId);
    document.getElementById('est_course').value = course;
    document.getElementById('est_year').value = year;
    document.getElementById('est_section').value = section;

    const semEl = document.getElementById('est_semester');
    const rawSem = typeof semester === 'string' ? semester : String(semester || '');
    const semNorm = { '1st semester': '1st', '2nd semester': '2nd', '1st Semester': '1st', '2nd Semester': '2nd', '2nd semest': '2nd' };
    let s = semNorm[rawSem] || rawSem;
    semEl.value = s || '1st';

    document.getElementById('est_sy').value = sy || '';
    editStudentStatusEl.value = studentStatus === 'irregular' ? 'irregular' : 'regular';

    editModal.classList.remove('hidden');
}

document.querySelectorAll('.open-edit-student-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        const deptRaw = btn.dataset.deptId;
        const deptId = deptRaw === undefined || deptRaw === '' ? null : deptRaw;
        openEditStudent(
            parseInt(btn.dataset.id, 10),
            btn.dataset.name,
            btn.dataset.studentId,
            btn.dataset.email,
            deptId,
            btn.dataset.course,
            parseInt(btn.dataset.year, 10),
            btn.dataset.section,
            btn.dataset.semester,
            btn.dataset.schoolYear,
            btn.dataset.status
        );
    });
});
</script>
@endpush
