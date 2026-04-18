@extends('layouts.app')

@section('title', 'Announcements')
@section('page-title', 'Announcements')

@section('content')
<div class="max-w-4xl">
    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    @forelse($announcements as $a)
        <a href="{{ route('announcements.show', $a) }}" class="block rounded-xl border border-gray-200 bg-white p-4 mb-3 hover:border-primary-light hover:shadow-sm transition">
            <div class="flex items-start gap-3">
                @if($a->is_pinned)
                    <span class="inline-flex items-center rounded bg-amber-100 text-amber-800 px-2 py-0.5 text-[11px] font-semibold">Pinned</span>
                @endif
                @if($a->priority === 'critical')
                    <span class="inline-flex items-center rounded bg-red-100 text-red-800 px-2 py-0.5 text-[11px] font-semibold">Critical</span>
                @elseif($a->priority === 'info')
                    <span class="inline-flex items-center rounded bg-blue-100 text-blue-800 px-2 py-0.5 text-[11px] font-semibold">Info</span>
                @endif
                <div class="flex-1">
                    <div class="text-base font-semibold text-slate-800">{{ $a->title }}</div>
                    <div class="text-sm text-slate-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit(strip_tags($a->body_html), 200) }}</div>
                    <div class="text-[11px] text-slate-400 mt-2">{{ ($a->publish_at ?? $a->created_at)->diffForHumans() }}</div>
                </div>
            </div>
        </a>
    @empty
        <div class="rounded-xl border border-dashed border-gray-200 p-8 text-center text-sm text-slate-500">
            No announcements for you.
        </div>
    @endforelse
</div>
@endsection
