<div class="max-w-3xl mx-auto mt-4">
    <x-card class="p-4">
        <div class="flex justify-between items-start gap-3">
            <div class="min-w-0">
                <h5 class="text-lg md:text-xl font-medium truncate">Review settings</h5>
                <p class="text-xs text-zinc-500">
                    {{ $review->subject?->name() }}
                    <span class="text-zinc-300 mx-1">|</span>
                    {{ $review->type->label() }} review
                </p>
            </div>

            <a href="{{ route('review', ['id' => $review->getKey()]) }}" class="btn-secondary p-2 shrink-0" title="Back to the review">
                <i class="fa fa-solid fa-arrow-left"></i>
            </a>
        </div>

        <hr class="my-4">

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
                @disabled(!$form->comments_enabled || $form->comments_enabled === '0')
                required
            >
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>

        @unless ($canEditBody)
            <div class="my-4 rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                <p class="text-sm text-zinc-600">
                    <i class="fa-solid fa-lock text-zinc-400 mr-1"></i>
                    This review is published. Only Song Rank Pro members can rewrite one after it is out &mdash;
                    everyone else deletes it and starts again.
                </p>
            </div>
        @endunless

        <div class="flex justify-between items-center gap-2 mt-6">
            <button type="button" class="btn-danger px-3 py-1" wire:click="confirmDestroy">
                <i class="fa fa-trash mr-1"></i> Delete
            </button>

            <div class="flex gap-2">
                @if ($canEditBody)
                    <a href="{{ route('review', ['id' => $review->getKey()]) }}" class="btn-secondary px-3 py-1">
                        Edit the words
                    </a>
                @endif

                <button type="button" class="btn-primary px-3 py-1" wire:click="update">
                    Save
                </button>
            </div>
        </div>
    </x-card>
</div>
