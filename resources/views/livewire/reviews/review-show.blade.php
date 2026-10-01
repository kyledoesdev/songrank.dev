<div>
    @if (! $review->is_published)
        <livewire:reviews.review-editor :review="$review" />
    @else
        <div class="p-4 bg-white shadow-lg rounded-lg mt-4">
            <div class="flex justify-between items-start gap-3">
                <div class="min-w-0">
                    <h5 class="text-base sm:text-lg md:text-xl font-medium">{{ $review->name }}</h5>
                    <p class="text-xs text-zinc-500">
                        {{ $review->subject?->name() }}
                        <span class="text-zinc-300 mx-1">|</span>
                        {{ $review->type->label() }} review
                    </p>
                </div>

                <div class="flex gap-1 sm:gap-2 shrink-0">
                    @if (auth()->id() === $review->user_id)
                        <a href="{{ route('review.edit', ['id' => $review->getKey()]) }}" class="btn-secondary p-1 sm:p-2">
                            <i class="fa fa-solid fa-pencil text-sm sm:text-base"></i>
                        </a>
                    @endif
                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-primary p-1 sm:p-2">
                            <i class="fa fa-solid fa-house text-sm sm:text-base"></i>
                        </a>
                    @endauth
                </div>
            </div>

            <hr class="my-4">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1 space-y-4">
                    <a href="{{ $review->subject->spotifyUrl() }}" target="_blank" rel="noopener noreferrer">
                        <img
                            src="{{ $review->subject->cover() }}"
                            alt="{{ $review->subject->name() }}"
                            class="w-full rounded-lg object-cover hover:opacity-90 transition-opacity"
                        >
                    </a>

                    <x-reviews.stars :review="$review" size="text-lg" />

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

                    @if ($review->is_public)
                        <x-reviews.share-buttons :review="$review" />
                    @endif
                </div>

                <div class="md:col-span-2">
                    <div class="prose prose-zinc max-w-none">
                        {!! $review->body !!}
                    </div>
                </div>
            </div>

            @if ($review->comments_enabled)
                <div class="mt-8 pb-8">
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
        </div>
    @endif
</div>
