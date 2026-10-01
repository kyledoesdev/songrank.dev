<div class="bg-white shadow-lg rounded-lg mt-4 p-4">
    <div class="flex justify-between items-start gap-3">
        <div class="min-w-0">
            <h5 class="text-base sm:text-lg md:text-xl font-medium truncate">{{ $review->name }}</h5>
            <p class="text-xs text-zinc-500">
                {{ $review->subject?->name() }}
                <span class="text-zinc-300 mx-1">|</span>
                {{ $review->type->label() }} review
                <span class="text-zinc-300 mx-1">|</span>
                Draft
            </p>
        </div>

        <div class="flex gap-1 sm:gap-2 shrink-0">
            <a href="{{ route('review.edit', ['id' => $review->getKey()]) }}" class="btn-secondary p-1 sm:p-2" title="Settings">
                <i class="fa fa-solid fa-gear text-sm sm:text-base"></i>
            </a>
            <a href="{{ route('dashboard') }}" class="btn-primary p-1 sm:p-2" title="Dashboard">
                <i class="fa fa-solid fa-house text-sm sm:text-base"></i>
            </a>
        </div>
    </div>

    <hr class="my-4">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 space-y-3">
            <label class="text-sm font-medium text-zinc-700">Your review</label>

            <flux:editor wire:model.blur="body" />

            <div class="flex justify-between items-center gap-2">
                <button type="button" class="btn-secondary px-3 py-1" wire:click="save">
                    Save draft
                </button>

                <button type="button" class="btn-primary px-3 py-1" wire:click="confirmPublish">
                    <span class="uppercase font-medium">Publish</span>
                </button>
            </div>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium text-zinc-700">Score</label>

                <x-reviews.star-input :stars="$stars" />

                @error('stars')
                    <p class="text-sm text-danger mt-1">{{ $message }}</p>
                @enderror
            </div>

            <img
                src="{{ $review->subject->cover() }}"
                alt="{{ $review->subject->name() }}"
                class="w-full rounded-lg object-cover"
            >
        </div>
    </div>
</div>
