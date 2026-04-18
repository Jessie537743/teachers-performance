@if(isset($criticalUnacked) && $criticalUnacked->isNotEmpty())
    @foreach($criticalUnacked as $a)
        <turbo-frame id="announcement-banner-{{ $a->id }}">
            <div class="w-full bg-red-600 text-white" role="alert">
                <div class="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-4">
                    <div class="flex-1">
                        <div class="font-semibold">{{ $a->title }}</div>
                        <div class="text-sm text-white/90 line-clamp-2">{!! $a->body_html !!}</div>
                    </div>
                    <form method="POST" action="{{ route('announcements.ack', $a) }}" class="m-0" data-turbo="false">
                        @csrf
                        <button type="submit" class="inline-flex items-center rounded-md bg-white/15 px-3 py-1.5 text-sm font-semibold hover:bg-white/25 transition">
                            I've read this
                        </button>
                    </form>
                </div>
            </div>
        </turbo-frame>
    @endforeach
@endif
