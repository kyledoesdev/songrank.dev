@props(['review'])

<x-card class="h-full cursor-pointer hover:shadow-lg transition-all duration-300 p-4" onclick="window.location.href='{{ route($review->is_published ? 'review' : 'review.edit', ['id' => $review->getKey()]) }}'">
    <div class="flex gap-4">
        <div class="shrink-0">
            <img
                src="{{ $review->subject->cover() }}"
                alt="{{ $review->subject->name() }}"
                class="h-24 w-24 rounded-lg object-cover"
            >
            <div class="mt-2 relative z-10">
                <x-spotify-logo :url="$review->subject->spotifyUrl()" />
            </div>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-xs uppercase tracking-wide text-zinc-400">
                <i class="fa-solid {{ $review->type->icon() }} mr-1"></i>
                {{ $review->type->label() }}
                @unless ($review->is_published)
                    <span class="ml-2 px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-500 normal-case">Draft</span>
                @endunless
            </p>

            <h5 class="text-base md:text-lg font-medium text-zinc-800 truncate">{{ $review->name }}</h5>

            <p class="text-sm text-zinc-500 truncate">{{ $review->subject->name() }}</p>

            <x-reviews.stars :review="$review" size="text-xs" class="mt-1" />

            @if (filled($review->excerpt(110)))
                <p class="mt-2 text-sm text-zinc-600 line-clamp-2">{{ $review->excerpt(110) }}</p>
            @endif

            <p class="mt-2 text-xs text-zinc-400">
                <i class="fa-regular fa-clock mr-0.5"></i>
                {{ $review->published_at }}

                @if (($review->comments_count ?? 0) > 0)
                    <span class="text-zinc-300 mx-1">|</span>
                    <i class="fa-regular fa-comment mr-0.5"></i>
                    {{ $review->comments_count }}
                @endif
            </p>
        </div>
    </div>
</x-card>
