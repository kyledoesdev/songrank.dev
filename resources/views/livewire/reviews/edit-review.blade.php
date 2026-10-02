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
                        @unless ($review->is_published)
                            <span class="text-zinc-300 mx-1">|</span>
                            Draft
                        @endunless
                    </p>
                </div>

                <div class="flex items-center shrink-0">
                    <button type="button" class="btn-danger my-0 px-2 py-1" wire:click="confirmDestroy" title="Delete this review">
                        <i class="fa fa-solid fa-trash text-sm"></i>
                    </button>

                    @if ($review->is_published)
                        <a href="{{ route('review', ['id' => $review->getKey()]) }}" class="btn-secondary my-0 px-2 py-1" title="View the review">
                            <i class="fa fa-solid fa-eye text-sm"></i>
                        </a>
                    @endif

                    <a href="{{ route('dashboard') }}" class="btn-primary my-0 px-2 py-1" title="Dashboard">
                        <i class="fa fa-solid fa-house text-sm"></i>
                    </a>
                </div>
            </div>
        </x-card.header>

        <section class="p-4">
            <h4 class="font-semibold text-gray-800 mb-2">Review Settings</h4>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-4">
                <div class="my-2">
                    <label>Name the Review:</label>
                    <input
                        type="text"
                        class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400"
                        wire:model="form.name"
                        maxlength="60"
                    />
                    @error('form.name') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="my-2">
                    <label>Show In Explore Feed?</label>
                    <select class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400" wire:model="form.is_public" required>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="my-2">
                    <label>Enable Comments</label>
                    <select class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400" wire:model.live="form.comments_enabled" required>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>

                <div class="my-2">
                    <label>Enable Comment Replies</label>
                    <select
                        class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400 disabled:opacity-50 disabled:cursor-not-allowed"
                        wire:model="form.comments_replies_enabled"
                        @disabled(! $form->comments_enabled || $form->comments_enabled === '0')
                        required
                    >
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="p-4 border-t border-zinc-200 space-y-4">
            <h4 class="font-semibold text-gray-800">The Review</h4>

            <div class="rounded-xl border border-zinc-200 overflow-hidden">
                <x-reviews.subject-header :review="$review">
                    @if ($this->canEditContent)
                        <x-reviews.star-input :stars="$stars" />
                        @error('stars') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    @else
                        <x-reviews.stars :review="$review" size="text-base sm:text-lg" />
                    @endif
                </x-reviews.subject-header>

                @if ($this->canEditContent)
                    <div class="p-4 sm:p-6">
                        <flux:editor
                            wire:model="body"
                            variant="borderless"
                            placeholder="Say your piece..."
                            toolbar="heading | bold italic strike | bullet ordered blockquote | link"
                        />
                        @error('body') <p class="text-sm text-danger mt-1">{{ $message }}</p> @enderror
                    </div>
                @else
                    <x-reviews.body :review="$review" class="p-4 sm:p-6" />
                @endif
            </div>

            @unless ($this->canEditContent)
                <x-pro-upgrade-banner>
                    <p class="mt-2 text-sm text-zinc-600">
                        Want to change a published review? Go Pro for full editing.
                    </p>
                </x-pro-upgrade-banner>
            @endunless
        </section>

        <x-card.footer class="flex justify-end items-center gap-2">
            <button type="button" class="btn-secondary px-3 py-1" wire:click="save">
                {{ $review->is_published ? 'Save Changes' : 'Save Draft' }}
            </button>

            @unless ($review->is_published)
                <button type="button" class="btn-primary px-3 py-1" wire:click="confirmPublish">
                    Publish
                </button>
            @endunless
        </x-card.footer>
    </x-card>
</div>
