{{-- Livewire partial: expects $type and $namePlaceholder; $form comes from the including component. --}}

@php($count = $this->bankCount())

<div>
    <div class="my-2" x-auto-animate>
        <label>Name the Tier List:</label>
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

    <p class="text-sm mb-2 text-zinc-500">
        {{ $count }} / {{ $this->bankLimit() }} {{ $type->itemLabel() }} on the board.
        @if ($count < 2)
            Add {{ 2 - $count }} more to start.
        @endif
    </p>

    <button
        type="button"
        class="btn-primary py-1 px-2"
        wire:click="confirmStartTierlist"
        @disabled($count < 2)
    >
        <h5 class="text-lg md:text-2xl uppercase cursor-pointer">Start Tier List</h5>
    </button>
</div>
