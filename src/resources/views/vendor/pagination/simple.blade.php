@if ($paginator->hasPages())
<nav class="ajax-pagination flex justify-center items-center gap-2 py-4">
    {{-- Previous --}}
    @if ($paginator->onFirstPage())
        <span class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 text-gray-400 cursor-not-allowed">&laquo; Prev</span>
    @else
        <a href="{{ $paginator->previousPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm bg-white text-gray-700 border border-gray-200 hover:bg-blue-600 hover:text-white hover:border-blue-600 hover:-translate-y-0.5 transition-all">&laquo; Prev</a>
    @endif

    {{-- Page Numbers --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="px-2 py-1.5 text-sm text-gray-400">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="px-3 py-1.5 rounded-lg text-sm bg-blue-600 text-white font-semibold">{{ $page }}</span>
                @else
                    <a href="{{ $url }}" class="px-3 py-1.5 rounded-lg text-sm bg-white text-gray-700 border border-gray-200 hover:bg-blue-600 hover:text-white hover:border-blue-600 hover:-translate-y-0.5 transition-all">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Next --}}
    @if ($paginator->hasMorePages())
        <a href="{{ $paginator->nextPageUrl() }}" class="px-3 py-1.5 rounded-lg text-sm bg-white text-gray-700 border border-gray-200 hover:bg-blue-600 hover:text-white hover:border-blue-600 hover:-translate-y-0.5 transition-all">Next &raquo;</a>
    @else
        <span class="px-3 py-1.5 rounded-lg text-sm bg-gray-100 text-gray-400 cursor-not-allowed">Next &raquo;</span>
    @endif
</nav>
@endif
