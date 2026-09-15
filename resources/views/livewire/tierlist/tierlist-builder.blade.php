<div class="bg-white shadow-md rounded-xl p-2 sm:p-4 mt-4">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-1 pb-3">
        <div class="min-w-0">
            <h5 class="text-base sm:text-lg md:text-xl font-medium truncate">{{ $tierlist->name }}</h5>
            <p class="text-xs text-zinc-500 mt-0.5">
                {{ $this->entryCount() }} {{ $tierlist->type->itemLabel() }}
                <span class="text-zinc-300 mx-1">|</span>
                Drag them into the tiers you want
            </p>
        </div>

        <div class="flex items-center gap-3 shrink-0">
            <x-toggle-switch wire:model.live="showEmbeds">
                Players
            </x-toggle-switch>

            <button
                type="button"
                class="btn-primary px-3 py-1.5"
                wire:click="confirmFinish"
                @disabled(! $this->canFinish())
                title="{{ $this->canFinish() ? 'Publish this tier list' : 'Place everything first' }}"
            >
                <i class="fa-solid fa-check mr-1"></i>
                Publish
            </button>
        </div>
    </div>

    @if ($showEmbeds)
        <p class="mx-1 mb-3 text-xs text-zinc-500 bg-amber-50 border border-amber-200 rounded-lg p-2">
            <i class="fa-solid fa-triangle-exclamation text-amber-500 mr-1"></i>
            Players make the tier list heavier. Turn them off if it starts feeling laggy.
        </p>
    @endif

    {{-- The tiers --}}
    <div class="space-y-1" x-auto-animate>
        @foreach ($tiers as $tier)
            <div class="flex items-stretch rounded-lg overflow-hidden border border-zinc-200" wire:key="tier-{{ $tier->getKey() }}">
                <div
                    class="w-20 sm:w-28 shrink-0 flex items-center justify-center p-2 text-zinc-800 font-bold text-center break-words leading-tight"
                    style="background-color: {{ $tier->color }}"
                >
                    {{ $tier->name }}
                </div>

                {{-- Controls sit beside the tier rather than on top of its colour. --}}
                <div class="w-7 shrink-0 flex flex-col items-center justify-center gap-1 bg-zinc-100 border-x border-zinc-200 text-zinc-400">
                    <button
                        type="button"
                        class="px-1 cursor-pointer hover:text-zinc-700 disabled:opacity-30 disabled:cursor-not-allowed"
                        wire:click="moveTier({{ $tier->getKey() }}, 'up')"
                        @disabled($loop->first)
                        title="Move up"
                    >
                        <i class="fa-solid fa-chevron-up text-[10px]"></i>
                    </button>

                    <button
                        type="button"
                        class="px-1 cursor-pointer hover:text-zinc-700"
                        wire:click="editTier({{ $tier->getKey() }})"
                        title="Tier settings"
                    >
                        <i class="fa-solid fa-gear text-xs"></i>
                    </button>

                    <button
                        type="button"
                        class="px-1 cursor-pointer hover:text-zinc-700 disabled:opacity-30 disabled:cursor-not-allowed"
                        wire:click="moveTier({{ $tier->getKey() }}, 'down')"
                        @disabled($loop->last)
                        title="Move down"
                    >
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </button>
                </div>

                <div
                    class="flex-1 flex flex-wrap gap-2 p-2 bg-zinc-50 min-h-24"
                    wire:sort="moveItem"
                    wire:sort:group="entries"
                    wire:sort:group-id="{{ $tier->getKey() }}"
                >
                    @foreach ($tier->items as $item)
                        @include('livewire.tierlist.partials.builder-tile', ['item' => $item, 'showEmbeds' => $showEmbeds])
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-2 px-1">
        <button type="button" class="text-xs px-3 py-1.5 rounded-lg border border-zinc-200 hover:border-zinc-300 cursor-pointer" wire:click="addTier">
            <i class="fa-solid fa-plus mr-1"></i>
            Add a tier
        </button>
    </div>

    {{-- Anything not placed yet --}}
    <div class="mt-4 rounded-lg border border-zinc-200 overflow-hidden">
        <div class="px-4 py-2 bg-gray-50 border-b border-zinc-200 flex items-center justify-between gap-2">
            <h4 class="font-semibold text-gray-800 text-sm">Unranked</h4>

            <span @class([
                'text-xs px-2 py-1 rounded-lg',
                'bg-green-100 text-green-700' => $this->canFinish(),
                'bg-zinc-100 text-zinc-600' => ! $this->canFinish(),
            ])>
                @if ($this->canFinish())
                    All placed
                @else
                    {{ $this->bankCount() }} still to place
                @endif
            </span>
        </div>

        <div
            class="flex flex-wrap gap-2 p-2 min-h-24"
            wire:sort="moveItem"
            wire:sort:group="entries"
            wire:sort:group-id="{{ $bank->getKey() }}"
        >
            @foreach ($bank->items as $item)
                @include('livewire.tierlist.partials.builder-tile', ['item' => $item, 'showEmbeds' => $showEmbeds])
            @endforeach
        </div>
    </div>

    {{-- Tier settings --}}
    @if ($editingTierId)
        <div
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            x-data
            x-on:keydown.escape.window="$wire.cancelEdit()"
            wire:key="tier-settings"
        >
            <div class="absolute inset-0 bg-black/50" wire:click="cancelEdit"></div>

            <div class="relative bg-white rounded-xl shadow-xl w-full max-w-sm p-4">
                <h5 class="font-semibold text-zinc-800 mb-3">Tier settings</h5>

                <label class="text-sm text-zinc-600">Name</label>
                <input
                    type="text"
                    class="w-full bg-zinc-100 rounded-lg p-2 mb-1 focus:ring-2 focus:ring-blue-400"
                    wire:model="tierName"
                    wire:keydown.enter="saveTier"
                    maxlength="20"
                    autofocus
                />
                @error('tierName') <p class="text-xs text-red-600 mb-2">{{ $message }}</p> @enderror

                <label class="text-sm text-zinc-600 mt-2 block">Colour</label>
                <input type="color" class="w-full h-10 rounded-lg cursor-pointer mb-1" wire:model="tierColor" />
                @error('tierColor') <p class="text-xs text-red-600 mb-2">{{ $message }}</p> @enderror

                <div class="flex items-center justify-between gap-2 mt-4">
                    <button type="button" class="btn-danger px-3 py-1.5 text-sm" wire:click="deleteTier({{ $editingTierId }})">
                        <i class="fa-solid fa-trash-can mr-1"></i>
                        Delete tier
                    </button>

                    <div class="flex gap-2">
                        <button type="button" class="text-sm px-3 py-1.5 rounded-lg border border-zinc-200 hover:border-zinc-300 cursor-pointer" wire:click="cancelEdit">
                            Cancel
                        </button>
                        <button type="button" class="btn-primary px-3 py-1.5 text-sm" wire:click="saveTier">
                            Save
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
