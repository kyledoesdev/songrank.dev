@props(['review'])

<div {{ $attributes->class(['flex items-center gap-4 p-4 bg-zinc-50 border-b border-zinc-200']) }}>
    <div class="shrink-0">
        <img
            src="{{ $review->subject->cover() }}"
            alt="{{ $review->subject->name() }}"
            class="h-20 w-20 sm:h-28 sm:w-28 rounded-lg object-cover shadow-sm"
        >
        <div class="mt-2">
            <x-spotify-logo :url="$review->subject->spotifyUrl()" />
        </div>
    </div>

    <div class="min-w-0 space-y-1">
        <p class="text-xs uppercase tracking-wide text-zinc-400">
            <i class="fa-solid {{ $review->type->icon() }} mr-1"></i>
            {{ $review->type->label() }}
        </p>
        <p class="text-lg sm:text-xl font-semibold text-zinc-800 truncate">{{ $review->subject->name() }}</p>

        {{ $slot }}
    </div>
</div>
