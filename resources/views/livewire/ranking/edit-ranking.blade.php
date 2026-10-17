<div class="mt-4">
    <x-card>
        <x-card.header>
            <div class="flex justify-between items-center gap-3">
                <div class="min-w-0">
                    <h5 class="text-base sm:text-lg md:text-xl font-medium truncate">{{ $ranking->name }}</h5>
                    <p class="text-xs text-zinc-500">
                        {{ $ranking->songs->count() }} {{ $ranking->type->itemLabel() }}
                        <span class="text-zinc-300 mx-1">|</span>
                        {{ $ranking->type->label() }} ranking
                    </p>
                </div>

                <div class="flex items-center shrink-0">
                    <button type="button" class="btn-danger my-0 px-2 py-1" wire:click="confirmDestroy" title="Delete this ranking">
                        <i class="fa fa-solid fa-trash text-sm"></i>
                    </button>

                    <a href="{{ route('ranking', ['id' => $ranking->getKey()]) }}" class="btn-secondary my-0 px-2 py-1" title="View the ranking">
                        <i class="fa fa-solid fa-eye text-sm"></i>
                    </a>

                    <a href="{{ route('dashboard') }}" class="btn-primary my-0 px-2 py-1" title="Dashboard">
                        <i class="fa fa-solid fa-house text-sm"></i>
                    </a>
                </div>
            </div>
        </x-card.header>

        <section class="p-4">
            <h4 class="font-semibold text-gray-800 mb-2">Ranking Settings</h4>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-4">
                <div class="my-2">
                    <label>Name the Ranking:</label>
                    <input
                        type="text"
                        class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400"
                        wire:model="form.name"
                        maxlength="30"
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

        <x-card.footer class="flex justify-end items-center gap-2">
            <button type="button" class="btn-secondary px-3 py-1" wire:click="update">
                Save Changes
            </button>
        </x-card.footer>
    </x-card>
</div>
