@extends('layouts.app')

@section('title', $announcement->title)
@section('page-title', 'Announcement')

@section('content')
<div class="max-w-3xl">
    <a href="{{ route('announcements.index') }}" class="inline-flex items-center text-sm text-slate-500 hover:text-slate-800 mb-4">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="mr-1"><path d="M15 18l-6-6 6-6"/></svg>
        Back to all announcements
    </a>

    <article class="rounded-xl border border-gray-200 bg-white p-6">
        <div class="flex items-center gap-2 mb-3">
            @if($announcement->priority === 'critical')
                <span class="inline-flex items-center rounded bg-red-100 text-red-800 px-2 py-0.5 text-[11px] font-semibold">Critical</span>
            @endif
            @if($announcement->is_pinned)
                <span class="inline-flex items-center rounded bg-amber-100 text-amber-800 px-2 py-0.5 text-[11px] font-semibold">Pinned</span>
            @endif
            <span class="text-xs text-slate-400">{{ ($announcement->publish_at ?? $announcement->created_at)->format('M j, Y g:i a') }}</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">{{ $announcement->title }}</h1>
        <div class="prose prose-slate max-w-none mt-4">{!! $announcement->body_html !!}</div>
    </article>
</div>
@endsection
