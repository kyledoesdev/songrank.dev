@use('App\Enums\ReviewType')

<div>
    {{-- Header + Search --}}
    <div class="mb-4 bg-white rounded-lg shadow-sm p-4 border border-gray-200">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div class="whitespace-nowrap">
                <h2 class="text-lg font-semibold text-zinc-800">
                    <i class="fa fa-compass text-primary-icon mr-1"></i>
                    Explore Reviews
                </h2>
                <p class="text-xs text-zinc-500 mt-0.5">
                    {{ number_format($totalReviews) }} published {{ Str::plural('review', $totalReviews) }} to explore
                </p>
            </div>

            <form wire:submit="performSearch" class="flex items-center gap-2 w-full sm:w-auto">
                <div class="relative flex-1 sm:flex-none sm:w-80">
                    <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm"></i>
                    <input
                        class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-300 rounded-full transition-all duration-300 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 focus:outline-none"
                        type="text"
                        placeholder="Search by artist, album, track or words"
                        wire:model="search"
                    />
                </div>

                <button type="submit" class="btn-primary px-3 py-1.5 cursor-pointer transform transition-all duration-300 hover:scale-110 active:scale-95" title="Search">
                    <i class="fa fa-magnifying-glass mt-1"></i>
                </button>

                <button type="button" class="btn-secondary px-3 py-1.5 cursor-pointer transform transition-all duration-300 hover:scale-110 active:scale-95" wire:click="resetSearch" title="Reset search">
                    <i class="fa-solid fa-rotate-left mt-1"></i>
                </button>
            </form>
        </div>

        {{-- Type filter --}}
        <div class="flex flex-wrap gap-2 mt-3">
            @foreach (ReviewType::cases() as $reviewType)
                <button
                    type="button"
                    wire:click="filterByType('{{ $reviewType->value }}')"
                    @class([
                        'flex items-center gap-2 px-3 py-1.5 rounded-full border-2 text-sm transition-all duration-300 cursor-pointer',
                        'border-purple-500 bg-purple-100 text-purple-700' => $type === $reviewType->value && $reviewType === ReviewType::ARTIST,
                        'border-green-500 bg-green-100 text-green-700' => $type === $reviewType->value && $reviewType === ReviewType::ALBUM,
                        'border-blue-500 bg-blue-100 text-blue-700' => $type === $reviewType->value && $reviewType === ReviewType::TRACK,
                        'border-zinc-300 bg-white text-zinc-500 hover:border-zinc-400' => $type !== $reviewType->value,
                    ])
                >
                    <i class="fa-solid {{ $reviewType->icon() }}"></i>
                    {{ $reviewType->label() }}
                </button>
            @endforeach
        </div>
    </div>

    @if ($this->reviews->count())
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach ($this->reviews as $review)
                <div
                    class="rounded-md cursor-pointer p-1 transform transition-all duration-300 hover:scale-101"
                    wire:key="review-{{ $review->getKey() }}"
                    wire:transition
                >
                    <x-reviews.card :review="$review" />
                </div>
            @endforeach
        </div>

        @if ($this->hasMorePages && ! $this->isFiltered)
            <div wire:intersect="loadMore" class="mt-4"></div>
        @endif
    @else
        <div class="flex justify-center">
            <span>No reviews found.</span>
        </div>
    @endif
</div>
