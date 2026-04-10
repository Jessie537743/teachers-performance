@extends('layouts.app')

@section('title', 'Subjects')
@section('page-title', 'Subject Management')

@section('content')
<div class="flex justify-between items-center gap-4 mb-5 flex-wrap animate-slide-up">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Subjects</h1>
        <p class="text-sm text-gray-500 mt-1">Manage course subjects and assigned teachers.</p>
    </div>
</div>

{{-- Add Button + Count --}}
<div class="flex gap-4 items-center mb-6">
    @can('manage-subjects')
    <button type="button" id="openAddSubjectBtn" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">+ Add Subject</button>
    <button type="button" id="openBulkSubjectUploadBtn" class="inline-flex items-center gap-2 bg-slate-100 text-slate-800 px-4 py-2.5 rounded-xl font-semibold hover:bg-slate-200 transition-all hover:-translate-y-0.5 shadow-sm">Upload Subject CSV</button>
    @endcan
    <span class="text-gray-500 text-sm">{{ $subjects->total() }} total</span>
</div>

@if(session('subject_bulk_upload_errors'))
    <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50 px-4 py-3">
        <p class="text-sm font-semibold text-amber-900 mb-1">Some rows were skipped during CSV upload:</p>
        <ul class="text-xs text-amber-800 space-y-1 list-disc pl-4">
            @foreach(session('subject_bulk_upload_errors') as $bulkError)
                <li>{{ $bulkError }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Subject CSV Upload Modal --}}
<div id="bulkSubjectUploadModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[680px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-1 text-lg font-bold text-slate-900">Upload Subjects via CSV</h3>
        <p class="text-sm text-gray-500 mb-5">Use the same requirements as Add Subject Details.</p>
        <div class="rounded-xl bg-slate-50 border border-slate-200 p-3 mb-4">
            <p class="text-xs text-slate-600 mb-2">Required columns:</p>
            <p class="text-xs text-slate-700"><code>code</code>, <code>title</code>, <code>department</code>, <code>course</code>, <code>year_level</code>, <code>section</code>, <code>semester</code></p>
            <p class="text-xs text-slate-600 mt-2">Optional columns:</p>
            <p class="text-xs text-slate-700"><code>school_year</code>, <code>instructor</code></p>
            <p class="text-xs text-slate-500 mt-2">Semester values accepted: <code>1st</code>, <code>2nd</code>, <code>Summer</code> (or First/Second Semester).</p>
            <a href="{{ route('subjects.bulk-template') }}" class="inline-flex mt-3 text-xs font-semibold text-blue-700 hover:text-blue-800 underline">
                Download CSV template
            </a>
        </div>
        <form method="POST" action="{{ route('subjects.bulk-upload') }}" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="form_source" value="subject_bulk_upload">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="subject_csv_file">CSV file</label>
                <input type="file" name="csv_file" id="subject_csv_file" accept=".csv,text/csv"
                       class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                @error('csv_file')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex gap-2.5 mt-5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Upload CSV</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" id="closeBulkSubjectUploadBtn">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Subjects list --}}
<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden transition hover:shadow-md">
    <div class="px-5 py-3.5 border-b border-gray-200 flex flex-col gap-4 lg:flex-row lg:flex-wrap lg:items-end lg:justify-between">
        <div class="flex items-center gap-3 flex-wrap">
            <span class="font-bold text-slate-900">All Subjects</span>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $subjects->total() }} total</span>
        </div>
        <form method="GET" action="{{ route('subjects.index') }}" class="flex flex-col sm:flex-row gap-2.5 w-full lg:w-auto lg:items-end flex-wrap">
            <div class="flex-1 min-w-[160px]">
                <label for="filter_subject_search" class="block text-xs font-semibold text-slate-600 mb-1">Title / description</label>
                <input type="text" name="search" id="filter_subject_search" value="{{ request('search') }}"
                       placeholder="Search title or code…"
                       class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
            </div>
            <div class="min-w-[200px]">
                <label for="filter_subject_dept" class="block text-xs font-semibold text-slate-600 mb-1">Department</label>
                <select name="department_id" id="filter_subject_dept"
                        class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">All departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ (string) request('department_id') === (string) $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[160px]">
                <label for="filter_subject_semester" class="block text-xs font-semibold text-slate-600 mb-1">Semester</label>
                <select name="semester" id="filter_subject_semester"
                        class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2.5 text-sm outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                    <option value="">All semesters</option>
                    <option value="1st" {{ request('semester') === '1st' ? 'selected' : '' }}>1st Semester</option>
                    <option value="2nd" {{ request('semester') === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                    <option value="Summer" {{ request('semester') === 'Summer' ? 'selected' : '' }}>Summer</option>
                </select>
            </div>
            <div class="flex gap-2 shrink-0 pb-0.5">
                <button type="submit" class="inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap">Search</button>
                @if(request()->filled('search') || request()->filled('department_id') || request()->filled('semester'))
                    <a href="{{ route('subjects.index') }}" class="inline-flex items-center justify-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition whitespace-nowrap">Clear</a>
                @endif
            </div>
        </form>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[700px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">#</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Code</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Title</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Department</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Course</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Year</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Section</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Semester</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Teacher Assigned</th>
                    @can('manage-subjects')<th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3.5 border-b border-gray-200 text-left">Actions</th>@endcan
                </tr>
            </thead>
            <tbody>
                @forelse($subjects as $subject)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ ($subjects->currentPage() - 1) * $subjects->perPage() + $loop->iteration }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle"><strong>{{ $subject->code }}</strong></td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $subject->title }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $subject->department?->name ?? '—' }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $subject->course }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">Year {{ $subject->year_level }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ $subject->section }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">{{ format_semester($subject->semester) }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        {{ $subject->assignments->first()?->faculty?->user?->name ?? '—' }}
                    </td>
                    @can('manage-subjects')
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <div class="flex gap-1.5">
                            <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition"
                                onclick="openEditSubject({{ $subject->id }}, '{{ addslashes($subject->code) }}', '{{ addslashes($subject->title) }}', {{ $subject->department_id }}, '{{ addslashes($subject->course) }}', {{ $subject->year_level }}, '{{ addslashes($subject->section) }}', '{{ $subject->semester }}', {{ $subject->assignments->first()?->faculty_id ?? 'null' }})">
                                Edit
                            </button>
                            <form method="POST" action="{{ route('subjects.destroy', $subject->id) }}"
                                  onsubmit="return confirm('Delete this subject?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition">Delete</button>
                            </form>
                        </div>
                    </td>
                    @endcan
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-gray-500 py-8 px-4">
                        @if(request()->filled('search') || request()->filled('department_id') || request()->filled('semester'))
                            No subjects match your filters.
                        @else
                            No subjects found. Add one using the button above.
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($subjects->hasPages())
    <div class="p-4 flex justify-center">
        {{ $subjects->links() }}
    </div>
    @endif
</div>

@php
    $oldDeptIds = collect(old('department_ids', []))->map(fn ($v) => (string) $v)->all();
@endphp
{{-- Add Subject Modal --}}
<div id="addSubjectModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-7 w-full max-w-3xl max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-1 text-lg font-bold text-slate-900">Add Subject Details</h3>
        <p class="text-sm text-gray-500 mb-5">Create one offering per selected program; section, year, semester, and teacher apply to each.</p>
        <form method="POST" action="{{ route('subjects.store') }}">
            @csrf
            <input type="hidden" name="form_source" value="subject_add">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-1 sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_code">Subject Code</label>
                    <input type="text" name="code" id="sub_code" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="e.g. CS101" value="{{ old('code') }}" required maxlength="50">
                </div>
                <div class="mb-1 sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_title">Subject Title</label>
                    <input type="text" name="title" id="sub_title" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                           placeholder="e.g. Introduction to Computing" value="{{ old('title') }}" required>
                </div>
                <div class="mb-1 sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_depts">Departments</label>
                    <select name="department_ids[]" id="sub_depts" multiple required size="8"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 min-h-[140px]">
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ in_array((string) $dept->id, $oldDeptIds, true) ? 'selected' : '' }}>
                                {{ $dept->name }}
                            </option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1.5">Select one or more departments (Ctrl/Cmd+click).</p>
                    @error('department_ids')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-1 sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_course_mode">Apply mode</label>
                    <select name="course_mode" id="sub_course_mode" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="specific" {{ old('course_mode', 'specific') === 'specific' ? 'selected' : '' }}>Select specific courses per department</option>
                        <option value="all" {{ old('course_mode') === 'all' ? 'selected' : '' }}>All courses under selected departments</option>
                    </select>
                </div>
                <div class="mb-1 sm:col-span-2" id="sub_courses_box">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_courses">Specific courses per department</label>
                    <select name="course_keys[]" id="sub_courses" multiple size="8"
                            class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-2 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 min-h-[160px]">
                    </select>
                    <p class="text-xs text-gray-500 mt-1.5">Each row is tied to a department (e.g. CCIS — BSIT). Programs listed match departments checked above.</p>
                    @error('course_keys')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                    @error('course_mode')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="mb-1">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_section">Section</label>
                    <select name="section" id="sub_section" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="">Select Section</option>
                        @foreach(range(1, 20) as $n)
                            <option value="{{ $n }}" {{ (string) old('section') === (string) $n ? 'selected' : '' }}>{{ $n }}</option>
                        @endforeach
                        @foreach(['A','B','C','D','E','F','G','H'] as $sec)
                            <option value="{{ $sec }}" {{ old('section') === $sec ? 'selected' : '' }}>{{ $sec }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-1">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_year">Year Level</label>
                    <select name="year_level" id="sub_year" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="">Select Year Level</option>
                        @for($y = 1; $y <= 6; $y++)
                            <option value="{{ $y }}" {{ old('year_level') == $y ? 'selected' : '' }}>Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-1">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_semester">Semester</label>
                    <select name="semester" id="sub_semester" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="">Select Semester</option>
                        <option value="1st" {{ old('semester') === '1st' ? 'selected' : '' }}>1st Semester</option>
                        <option value="2nd" {{ old('semester') === '2nd' ? 'selected' : '' }}>2nd Semester</option>
                        <option value="Summer" {{ old('semester') === 'Summer' ? 'selected' : '' }}>Summer</option>
                    </select>
                </div>
                <div class="mb-1 sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="sub_faculty">Teacher Assigned</label>
                    <select name="faculty_id" id="sub_faculty" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                        <option value="">No Teacher Assigned</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1.5">Active personnel with a faculty profile (sorted by name). The same assignee is applied to every new offering created in this batch.</p>
                    @error('faculty_id')<p class="text-sm text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="mt-4 rounded-xl border border-blue-100 bg-blue-50/90 px-4 py-3 text-sm text-blue-950 leading-relaxed">
                In <strong>Select specific courses per department</strong> mode, you choose exact programs so unrelated degrees are not added automatically.
                In <strong>All courses under selected departments</strong> mode, an offering is created for every active program in each department you checked.
                The <strong>section</strong>, <strong>year level</strong>, <strong>semester</strong>, and optional <strong>teacher</strong> apply to each new row.
            </div>
            <div class="flex gap-2.5 mt-5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Add Subject</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" id="closeAddSubjectBtn">Cancel</button>
            </div>
        </form>
    </div>
</div>

{{-- Edit Subject Modal --}}
<div id="editSubjectModal" class="hidden fixed inset-0 bg-slate-900/50 z-[2000] items-center justify-center">
    <div class="bg-white rounded-2xl p-7 w-full max-w-[580px] max-h-[90vh] overflow-y-auto shadow-2xl">
        <h3 class="mb-5 text-lg font-bold text-slate-900">Edit Subject</h3>
        <form method="POST" id="editSubjectForm">
            @csrf @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Code</label>
                    <input type="text" name="code" id="esub_code" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required maxlength="50">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Title</label>
                    <input type="text" name="title" id="esub_title" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Department</label>
                    <select name="department_id" id="esub_dept" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Course</label>
                    <input type="text" name="course" id="esub_course" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Year Level</label>
                    <select name="year_level" id="esub_year" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        @for($y = 1; $y <= 6; $y++)
                            <option value="{{ $y }}">Year {{ $y }}</option>
                        @endfor
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Section</label>
                    <input type="text" name="section" id="esub_section" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Semester</label>
                    <select name="semester" id="esub_semester" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="1st Semester">1st Semester</option>
                        <option value="2nd Semester">2nd Semester</option>
                        <option value="Summer">Summer</option>
                    </select>
                </div>
                <div class="mb-4 sm:col-span-2">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="esub_faculty">Teacher assigned</label>
                    <select name="faculty_id" id="esub_faculty" class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10" required>
                        <option value="">-- Select Teacher --</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-2.5 mt-1.5">
                <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition-all hover:-translate-y-0.5 shadow-sm">Save Changes</button>
                <button type="button" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-4 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition" onclick="document.getElementById('editSubjectModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function addSubjectModalOpen() {
    const m = document.getElementById('addSubjectModal');
    if (!m) return;
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function addSubjectModalClose() {
    const m = document.getElementById('addSubjectModal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
}

function bulkSubjectUploadModalOpen() {
    const m = document.getElementById('bulkSubjectUploadModal');
    if (!m) return;
    m.classList.remove('hidden');
    m.classList.add('flex');
}
function bulkSubjectUploadModalClose() {
    const m = document.getElementById('bulkSubjectUploadModal');
    if (!m) return;
    m.classList.add('hidden');
    m.classList.remove('flex');
}

@if($errors->any() && old('form_source') === 'subject_bulk_upload')
    bulkSubjectUploadModalOpen();
@elseif($errors->any() && !old('_method'))
    addSubjectModalOpen();
@endif

document.getElementById('openAddSubjectBtn')?.addEventListener('click', addSubjectModalOpen);
document.getElementById('closeAddSubjectBtn')?.addEventListener('click', addSubjectModalClose);
document.getElementById('openBulkSubjectUploadBtn')?.addEventListener('click', bulkSubjectUploadModalOpen);
document.getElementById('closeBulkSubjectUploadBtn')?.addEventListener('click', bulkSubjectUploadModalClose);

document.getElementById('addSubjectModal')?.addEventListener('click', function(e) {
    if (e.target === this) addSubjectModalClose();
});
document.getElementById('bulkSubjectUploadModal')?.addEventListener('click', function(e) {
    if (e.target === this) bulkSubjectUploadModalClose();
});
document.getElementById('editSubjectModal')?.addEventListener('click', function(e) {
    if (e.target === this) this.style.display = 'none';
});

const coursesForAdd = @json($coursesForAdd);
const facultyList = @json($facultyListForSubjects);

const oldCourseKeys = @json(collect(old('course_keys', []))->values()->all());

const subDeptsEl = document.getElementById('sub_depts');
const subCourseModeEl = document.getElementById('sub_course_mode');
const subCoursesEl = document.getElementById('sub_courses');
const subCoursesBoxEl = document.getElementById('sub_courses_box');
const subFacultyEl = document.getElementById('sub_faculty');
const esubDeptEl = document.getElementById('esub_dept');
const esubFacultyEl = document.getElementById('esub_faculty');

function getSelectedDeptIds() {
    if (!subDeptsEl) return [];
    return Array.from(subDeptsEl.selectedOptions).map((o) => String(o.value)).filter(Boolean);
}

function buildFacultySelectOptions(selectEl, deptId, selectedId) {
    if (!selectEl) return;
    selectEl.innerHTML = '<option value="">-- Select Teacher --</option>';
    facultyList.forEach((faculty) => {
        const option = document.createElement('option');
        option.value = faculty.id;
        option.textContent = faculty.dept_code ? (faculty.name + ' (' + faculty.dept_code + ')') : faculty.name;
        selectEl.appendChild(option);
    });
    if (selectedId != null && selectedId !== '') {
        const n = Number(selectedId);
        if (!Number.isNaN(n)) {
            selectEl.value = String(n);
            if (selectEl.value !== String(n)) {
                const opt = document.createElement('option');
                opt.value = String(n);
                opt.textContent = 'Current assignee (#' + n + ')';
                opt.selected = true;
                selectEl.appendChild(opt);
            }
        }
    }
}

function refreshAddCourseOptions() {
    if (!subCoursesEl) return;
    const deptSet = new Set(getSelectedDeptIds());
    const previous = new Set(oldCourseKeys);

    subCoursesEl.innerHTML = '';
    coursesForAdd.forEach((row) => {
        if (!deptSet.has(String(row.department_id))) return;
        const opt = document.createElement('option');
        opt.value = row.value;
        opt.textContent = row.label;
        if (previous.has(row.value)) opt.selected = true;
        subCoursesEl.appendChild(opt);
    });
}

function refreshAddFacultyOptions() {
    if (!subFacultyEl) return;
    const prev = @json(old('faculty_id'));

    subFacultyEl.innerHTML = '<option value="">No Teacher Assigned</option>';
    facultyList.forEach((f) => {
        const opt = document.createElement('option');
        opt.value = f.id;
        opt.textContent = f.dept_code ? (f.name + ' (' + f.dept_code + ')') : f.name;
        subFacultyEl.appendChild(opt);
    });
    if (prev !== null && prev !== '') {
        subFacultyEl.value = String(prev);
        if (subFacultyEl.value !== String(prev)) {
            const found = facultyList.find((x) => String(x.id) === String(prev));
            const opt = document.createElement('option');
            opt.value = String(prev);
            opt.textContent = found ? (found.name + ' (current)') : ('Teacher #' + prev);
            opt.selected = true;
            subFacultyEl.appendChild(opt);
        }
    }
}

function toggleAddCourseModeUI() {
    if (!subCourseModeEl || !subCoursesBoxEl || !subCoursesEl) return;
    const specific = subCourseModeEl.value === 'specific';
    subCoursesBoxEl.classList.toggle('hidden', !specific);
    subCoursesEl.required = specific;
    if (!specific) {
        Array.from(subCoursesEl.options).forEach((o) => { o.selected = false; });
    }
}

subDeptsEl?.addEventListener('change', () => {
    refreshAddCourseOptions();
});
subCourseModeEl?.addEventListener('change', toggleAddCourseModeUI);
refreshAddCourseOptions();
refreshAddFacultyOptions();
toggleAddCourseModeUI();

function openEditSubject(id, code, title, deptId, course, year, section, semester, facultyId) {
    document.getElementById('editSubjectForm').action = @json(url('subjects')) + '/' + id;
    document.getElementById('esub_code').value = code;
    document.getElementById('esub_title').value = title;
    document.getElementById('esub_dept').value = deptId;
    document.getElementById('esub_course').value = course;
    document.getElementById('esub_year').value = year;
    document.getElementById('esub_section').value = section;
    const semEl = document.getElementById('esub_semester');
    const semNorm = { '1st semester': '1st', '2nd semester': '2nd', '1st Semester': '1st', '2nd Semester': '2nd' };
    const s = typeof semester === 'string' ? (semNorm[semester] || semester) : semester;
    semEl.value = s;
    if (semEl.value !== s && semester) {
        const opt = document.createElement('option');
        opt.value = semester;
        opt.textContent = semester;
        opt.selected = true;
        semEl.appendChild(opt);
    }
    buildFacultySelectOptions(esubFacultyEl, deptId, facultyId);
    document.getElementById('editSubjectModal').style.display = 'flex';
}
</script>
@endpush
