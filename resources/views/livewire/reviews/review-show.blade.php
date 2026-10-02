<div class="mt-4">
    <x-card>
        <x-card.header>
            <div class="flex justify-between items-center gap-3">
                <div class="min-w-0">
                    <h5 class="text-base sm:text-lg md:text-xl font-medium truncate">{{ $review->name }}</h5>
                    <p class="text-xs text-zinc-500">
                        {{ $review->subject->name() }}
                        <span class="text-zinc-300 mx-1">|</span>
                        {{ $review->type->label() }} review
                    </p>
                </div>

                <div class="flex items-center shrink-0">
                    @if ($review->is_public)
                        <x-share-buttons
                            :url="route('review', ['id' => $review->getKey()])"
                            :text="$review->shareText()"
                        />
                    @endif

                    @if (auth()->id() === $review->user_id)
                        <a href="{{ route('review.edit', ['id' => $review->getKey()]) }}" class="btn-secondary my-0 px-2 py-1" title="Edit">
                            <i class="fa fa-solid fa-pencil text-sm"></i>
                        </a>
                    @endif

                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary my-0 px-2 py-1" title="Dashboard">
                            <i class="fa fa-solid fa-house text-sm"></i>
                        </a>
                    @endauth
                </div>
            </div>
        </x-card.header>

        <div class="p-4 space-y-4">
            <div class="bg-primary-soft border border-primary-soft rounded-lg p-3 flex items-center gap-3">
                @if ($review->user->avatar)
                    <img src="{{ $review->user->avatar }}" alt="{{ $review->user->name }}" class="h-10 w-10 rounded-full object-cover shrink-0">
                @else
                    <div class="h-10 w-10 rounded-full bg-primary-muted flex items-center justify-center shrink-0">
                        <i class="fa-regular fa-user text-primary-icon"></i>
                    </div>
                @endif

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-zinc-800 truncate">
                        Reviewed by
                        <a href="{{ route('profile', ['id' => $review->user->spotify_id]) }}" class="text-primary hover:underline">
                            {{ $review->user->name }}
                            <i class="fa fa-arrow-up-right-from-square text-blue-500 text-xs"></i>
                        </a>
                    </p>
                    <p class="text-xs text-zinc-500 truncate" title="{{ $review->formatted_published_at }}">
                        <i class="fa-regular fa-clock mr-0.5"></i>
                        {{ $review->published_at }}
                    </p>
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 overflow-hidden">
                <x-reviews.subject-header :review="$review">
                    <x-reviews.stars :review="$review" size="text-base sm:text-lg" />
                </x-reviews.subject-header>

                <x-reviews.body :review="$review" class="p-4 sm:p-6" />
            </div>
        </div>

        @if ($review->comments_enabled)
            <div class="px-4 pb-8">
                @if ($review->comments_replies_enabled)
                    <livewire:comments
                        :model="$review"
                        newest-first
                        no-comments-text="No comments yet - you could be the first!"
                    />
                @else
                    <livewire:comments
                        :model="$review"
                        newest-first
                        no-replies
                        no-comments-text="No comments yet - you could be the first!"
                    />
                @endif
            </div>
        @endif
    </x-card>
</div>
