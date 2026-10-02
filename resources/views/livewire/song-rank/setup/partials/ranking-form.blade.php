@use('App\Models\Ranking')

{{-- Livewire partial: expects $namePlaceholder, $tracksToRankCount, and $itemLabel; $form comes from the including component. --}}

<div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
    <x-card.header>
        <h4 class="font-semibold text-gray-800">Ranking Settings</h4>
    </x-card.header>

    <div class="p-4">
        <div class="my-2" x-auto-animate>
            <label>Name the Ranking:</label>
            <input
                type="text"
                class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400"
                placeholder="{{ $namePlaceholder }}"
                wire:model.live.debounce.500ms="form.name"
                maxlength="30"
            />
        </div>

        <div class="my-2" x-auto-animate>
            <label>Show In Explore Feed?</label>
            <select
                class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400"
                wire:model.live.debounce.500ms="form.is_public"
                required
            >
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="my-2" x-auto-animate>
            <label>Enable Comments</label>
            <select
                class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400"
                wire:model.live.debounce.500ms="form.comments_enabled"
                required
            >
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>

        <div class="my-2" x-auto-animate>
            <label>Enable Comment Replies</label>
            <select
                class="w-full bg-zinc-100 rounded-lg p-2 focus:ring-2 focus:ring-blue-400 disabled:opacity-50 disabled:cursor-not-allowed"
                wire:model.live.debounce.500ms="form.comments_replies_enabled"
                @disabled(!$form->comments_enabled || $form->comments_enabled === '0')
                required
            >
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </div>

        <p @class([
            'text-sm mb-2',
            'text-red-600 font-semibold' => $tracksToRankCount > Ranking::MAX_SONGS,
            'text-zinc-500' => $tracksToRankCount <= Ranking::MAX_SONGS,
        ])>
            {{ $tracksToRankCount }} / {{ Ranking::MAX_SONGS }} {{ $itemLabel }} selected.
            @if ($tracksToRankCount > Ranking::MAX_SONGS)
                Remove {{ $tracksToRankCount - Ranking::MAX_SONGS }} to begin.
            @endif
        </p>

        <button
            type="button"
            class="btn-primary py-1 px-2"
            wire:click="confirmBeginRanking"
        >
            <h5 class="text-lg md:text-2xl uppercase cursor-pointer">Begin Ranking</h5>
        </button>
    </div>
</div>
