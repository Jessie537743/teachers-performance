@extends('layouts.app')

@section('title', $subject->code.' — Subject details')
@section('page-title', 'Subject details')

@section('content')
<div class="mb-6 animate-slide-up">
    <a href="{{ route('subjects.index') }}" class="text-sm font-semibold text-blue-700 hover:text-blue-900">&larr; Back to Subject Management</a>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden mb-6">
    <div class="px-6 py-5 border-b border-gray-200 bg-slate-50/80">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $subject->title }}</h1>
                <p class="text-sm text-gray-600 mt-1">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">{{ $subject->code }}</span>
                    <span class="text-gray-400 mx-1">&bull;</span>
                    {{ $subject->department?->name ?? '—' }}
                </p>
            </div>
            @can('manage-subjects')
            <a href="{{ route('subjects.edit', $subject) }}"
               class="inline-flex items-center justify-center gap-2 bg-blue-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-blue-700 transition whitespace-nowrap shrink-0">
                Edit this offering
            </a>
            @endcan
        </div>
    </div>
    <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Course / Program</div>
            <div class="font-medium text-slate-900 mt-0.5">{{ $subject->course }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Year level</div>
            <div class="font-medium text-slate-900 mt-0.5">Year {{ $subject->year_level }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Semester</div>
            <div class="font-medium text-slate-900 mt-0.5">{{ $subject->semester }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">School year</div>
            <div class="font-medium text-slate-900 mt-0.5">{{ $subject->school_year ?: '—' }}</div>
        </div>
    </div>
</div>

<div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-bold text-slate-900">Section offerings &amp; teachers</h2>
        <p class="text-sm text-gray-500 mt-1">All sections for this subject code with the same program, year level, term, and school year. Each section is stored as its own row; you assign one teacher per section.</p>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full border-collapse min-w-[640px]">
            <thead>
                <tr>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3 border-b border-gray-200 text-left">Section</th>
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3 border-b border-gray-200 text-left">Teacher assigned</th>
                    @can('manage-subjects')
                    <th class="bg-gray-50 text-slate-700 font-bold text-sm px-4 py-3 border-b border-gray-200 text-left">Actions</th>
                    @endcan
                </tr>
            </thead>
            <tbody>
                @foreach($offerings as $offering)
                <tr class="hover:bg-blue-50/50 transition-colors">
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle font-semibold text-slate-900">{{ $offering->section }}</td>
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        {{ $offering->assignments->first()?->faculty?->user?->name ?? '—' }}
                    </td>
                    @can('manage-subjects')
                    <td class="px-4 py-3.5 border-b border-gray-200 align-middle">
                        <a href="{{ route('subjects.edit', $offering) }}"
                           class="inline-flex items-center gap-2 bg-gray-200 text-slate-900 px-3 py-1.5 rounded-xl text-sm font-semibold hover:bg-gray-300 transition">
                            Edit
                        </a>
                    </td>
                    @endcan
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
