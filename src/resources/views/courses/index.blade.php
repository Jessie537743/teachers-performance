@extends('layouts.app')

@section('title', 'Courses')
@section('page-title', 'Course Management')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Courses</h1>
        <p class="text-sm text-gray-500 mt-1">Manage degree programs offered by each department.</p>
    </div>
</div>

<div class="flex flex-wrap gap-4 items-center mb-6">
    @can('manage-courses')
    <button type="button" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm" onclick="document.getElementById('addCourseModal').style.display='flex'">+ Add Course</button>
    @endcan
    <span class="text-gray-500 text-sm">{{ $courses->total() }} total</span>
</div>

<form method="GET" action="{{ route('courses.index') }}" class="mb-5">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
        <input
            type="text"
            name="course_code"
            value="{{ $courseCode ?? '' }}"
            placeholder="Filter course code..."
            class="border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
        >
        <input
            type="text"
            name="description"
            value="{{ $description ?? '' }}"
            placeholder="Filter description..."
            class="border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
        >
        <select
            name="department_id"
            class="border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
        >
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ (string) ($departmentId ?? '') === (string) $dept->id ? 'selected' : '' }}>
                    {{ $dept->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="flex items-center gap-2 mt-2">
        <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-sm">
            Filter
        </button>
        @if(!empty($courseCode) || !empty($description) || !empty($departmentId))
            <a href="{{ route('courses.index') }}" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition-all">
                Clear
            </a>
        @endif
    </div>
</form>

{{-- Add Course Modal --}}
<div id="addCourseModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[520px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Add Course</h3>
        <form method="POST" action="{{ route('courses.store') }}">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="c_code">Course Code</label>
                <input type="text" name="course_code" id="c_code" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. BSIT" value="{{ old('course_code') }}" required maxlength="50">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="c_name">Course Name</label>
                <input type="text" name="course_name" id="c_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                       placeholder="e.g. Bachelor of Science in Information Technology"
                       value="{{ old('course_name') }}" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="c_dept">Department</label>
                <select name="department_id" id="c_dept" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    <option value="">-- Select Department --</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Add Course</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('addCourseModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Courses Table --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex justify-between items-center">
        <span class="font-bold text-slate-900">All Courses</span>
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $courses->total() }} total</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Code</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Name</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Status</th>
                    @can('manage-courses')<th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Actions</th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($courses as $course)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($courses->currentPage() - 1) * $courses->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $course->course_code }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $course->course_name }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $course->department?->name ?? '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        @if($course->is_active)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Active</span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-700">Inactive</span>
                        @endif
                    </td>
                    @can('manage-courses')
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <div class="flex gap-1.5">
                            <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition"
                                onclick="openEditCourse({{ $course->id }}, '{{ addslashes($course->course_code) }}', '{{ addslashes($course->course_name) }}', {{ $course->department_id }})">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('courses.destroy', $course->id) }}"
                                  onsubmit="return confirm('Delete this course?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">Delete</button>
                            </form>
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-gray-500 py-8 px-4">
                        No courses in the catalog yet. Run <code class="text-xs bg-gray-100 px-1 rounded">php artisan db:seed --class=CourseSeeder</code> or add one above.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($courses->hasPages())
    <div class="p-4 flex justify-center">
        {{ $courses->links() }}
    </div>
    @endif
</div>

{{-- Edit Course Modal --}}
<div id="editCourseModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[520px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Edit Course</h3>
        <form method="POST" id="editCourseForm">
            @csrf @method('PUT')
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Course Code</label>
                <input type="text" name="course_code" id="ec_code" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required maxlength="50">
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Course Name</label>
                <input type="text" name="course_name" id="ec_name" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Department</label>
                <select name="department_id" id="ec_dept" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('editCourseModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
@if($errors->any() && !old('_method'))
    document.getElementById('addCourseModal').style.display = 'flex';
@endif

document.getElementById('addCourseModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});
document.getElementById('editCourseModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

function openEditCourse(id, code, name, deptId) {
    document.getElementById('editCourseForm').action = @json(url('courses')) + '/' + id;
    document.getElementById('ec_code').value = code;
    document.getElementById('ec_name').value = name;
    document.getElementById('ec_dept').value = deptId;
    document.getElementById('editCourseModal').style.display = 'flex';
}
</script>
@endpush
