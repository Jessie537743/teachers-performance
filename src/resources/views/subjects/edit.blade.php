@extends('layouts.app')

@section('title', 'Edit '.$subject->code)
@section('page-title', 'Edit subject')

@section('content')
<div class="mb-6 animate-slide-up flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('subjects.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">&larr; Back to Subject Management</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-2">Edit subject</h1>
        <p class="text-sm text-gray-500 mt-1">Update code, schedule fields, and teacher assignment for this offering.</p>
    </div>
    <a href="{{ route('subjects.show', $subject) }}" class="text-sm font-semibold text-slate-600 hover:text-slate-900">View details</a>
</div>

@php
    $semesterValue = old('semester', $subject->semester);
    if (! in_array($semesterValue, ['1st', '2nd', 'Summer'], true)) {
        $low = mb_strtolower((string) $semesterValue);
        if (str_contains($low, '1st') || str_contains($low, 'first')) {
            $semesterValue = '1st';
        } elseif (str_contains($low, '2nd') || str_contains($low, 'second')) {
            $semesterValue = '2nd';
        } elseif (str_contains($low, 'summer')) {
            $semesterValue = 'Summer';
        }
    }
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <p class="font-semibold mb-1">Please fix the following:</p>
        <ul class="list-disc pl-5 space-y-0.5">
            @foreach ($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ route('subjects.update', $subject) }}" class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6 sm:p-8 max-w-3xl">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="code">Subject code</label>
            <input type="text" name="code" id="code" required maxlength="50"
                   value="{{ old('code', $subject->code) }}"
                   class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="title">Title</label>
            <input type="text" name="title" id="title" required
                   value="{{ old('title', $subject->title) }}"
                   class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="department_id">Department</label>
            <select name="department_id" id="department_id" required
                    class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                @foreach($departments as $dept)
                    <option value="{{ $dept->id }}" @selected(old('department_id', $subject->department_id) == $dept->id)>{{ $dept->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="course">Course / program</label>
            <input type="text" name="course" id="course" required maxlength="100"
                   value="{{ old('course', $subject->course) }}"
                   class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="year_level">Year level</label>
            <select name="year_level" id="year_level" required
                    class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                @for($y = 1; $y <= 6; $y++)
                    <option value="{{ $y }}" @selected((int) old('year_level', $subject->year_level) === $y)>Year {{ $y }}</option>
                @endfor
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="section">Section</label>
            <input type="text" name="section" id="section" required maxlength="50"
                   value="{{ old('section', $subject->section) }}"
                   class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
                   placeholder="e.g. 1, 2, or A">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="semester">Semester</label>
            <select name="semester" id="semester" required
                    class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                @foreach(['1st' => '1st Semester', '2nd' => '2nd Semester', 'Summer' => 'Summer'] as $val => $label)
                    <option value="{{ $val }}" @selected($semesterValue === $val)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="school_year">School year <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" name="school_year" id="school_year" maxlength="20"
                   value="{{ old('school_year', $subject->school_year) }}"
                   placeholder="e.g. 2025-2026"
                   class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
        </div>
        <div class="sm:col-span-2">
            <label class="block text-sm font-semibold text-slate-700 mb-1.5" for="faculty_id">Teacher assigned</label>
            <select name="faculty_id" id="faculty_id"
                    class="w-full border border-gray-200 bg-white rounded-xl px-3.5 py-3 outline-none transition focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10">
                <option value="">No teacher assigned</option>
                @php $curFaculty = old('faculty_id', $subject->assignments->first()?->faculty_id); @endphp
                @foreach($facultyListForSubjects as $f)
                    <option value="{{ $f['id'] }}" @selected((string) $curFaculty === (string) $f['id'])>
                        {{ $f['name'] }}{{ $f['dept_code'] ? ' ('.$f['dept_code'].')' : '' }}
                    </option>
                @endforeach
            </select>
            <p class="text-xs text-gray-500 mt-1.5">Leave empty to remove the current teacher assignment.</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-2.5 mt-8 pt-6 border-t border-gray-100">
        <button type="submit" class="inline-flex items-center gap-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition shadow-sm">
            Save changes
        </button>
        <a href="{{ route('subjects.index') }}" class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-5 py-2.5 rounded-xl font-semibold hover:bg-gray-300 transition">
            Cancel
        </a>
    </div>
</form>
@endsection
