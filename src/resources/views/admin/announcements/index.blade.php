@extends('layouts.app')

@section('title', 'Manage Announcements')
@section('page-title', 'Manage Announcements')

@section('content')
<div class="max-w-6xl">
    <div class="flex items-center justify-between mb-4">
        <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5">
            @foreach(['published', 'draft', 'archived'] as $s)
                <a href="{{ route('admin.announcements.index', ['status' => $s]) }}"
                   class="px-3 py-1.5 text-sm rounded-md {{ $status === $s ? 'bg-primary text-white font-semibold' : 'text-slate-600 hover:bg-gray-50' }}">
                    {{ ucfirst($s) }}
                </a>
            @endforeach
        </div>
        <a href="{{ route('admin.announcements.create') }}" class="inline-flex items-center rounded-md bg-primary text-white px-4 py-2 text-sm font-semibold hover:bg-primary-dark transition">
            + New Announcement
        </a>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
    @endif

    <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50 text-left">
                <tr>
                    <th class="px-4 py-3 font-semibold">Title</th>
                    <th class="px-4 py-3 font-semibold">Priority</th>
                    <th class="px-4 py-3 font-semibold">Scope</th>
                    <th class="px-4 py-3 font-semibold">Publish</th>
                    <th class="px-4 py-3 font-semibold">Expires</th>
                    <th class="px-4 py-3 font-semibold">Author</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($announcements as $a)
                    <tr class="border-t border-gray-100">
                        <td class="px-4 py-3">
                            @if($a->is_pinned) <span class="mr-1 text-amber-600">★</span>@endif
                            {{ $a->title }}
                            @if($a->show_on_login) <span class="ml-1 text-[11px] text-blue-600">(login)</span>@endif
                        </td>
                        <td class="px-4 py-3">{{ ucfirst($a->priority) }}</td>
                        <td class="px-4 py-3">{{ $a->everyone ? 'Everyone' : 'Targeted' }}</td>
                        <td class="px-4 py-3">{{ $a->publish_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->expires_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $a->creator?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-1">
                            <a href="{{ route('admin.announcements.edit', $a) }}" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs hover:bg-gray-50">Edit</a>
                            @if($status !== 'archived')
                                <form method="POST" action="{{ route('admin.announcements.archive', $a) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center rounded-md border border-gray-200 bg-white px-2.5 py-1 text-xs hover:bg-gray-50">Archive</button>
                                </form>
                            @endif
                            <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" class="inline" onsubmit="return confirm('Delete permanently?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center rounded-md border border-red-200 bg-white text-red-600 px-2.5 py-1 text-xs hover:bg-red-50">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">No announcements in this status.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $announcements->links() }}</div>
</div>
@endsection
