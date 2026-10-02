<div>
    <x-card class="mt-4">
        <x-card.header>
            <div class="flex justify-between items-center gap-3">
                <div class="min-w-0">
                    <h5 class="text-base sm:text-lg md:text-xl font-medium truncate">{{ $tierlist->name }}</h5>
                    <p class="text-xs text-zinc-500">
                        {{ $tierlist->items->count() }} {{ $tierlist->type->itemLabel() }}
                        <span class="text-zinc-300 mx-1">|</span>
                        {{ $tierlist->type->label() }} tier list
                    </p>
                </div>

                <div class="flex items-center shrink-0">
                    <button type="button" class="btn-danger my-0 px-2 py-1" wire:click="confirmDestroy" title="Delete this tier list">
                        <i class="fa fa-solid fa-trash text-sm"></i>
                    </button>

                    <a href="{{ route('tierlist', ['id' => $tierlist->getKey()]) }}" class="btn-secondary my-0 px-2 py-1" title="View the tier list">
                        <i class="fa fa-solid fa-eye text-sm"></i>
                    </a>

                    <a href="{{ route('dashboard') }}" class="btn-primary my-0 px-2 py-1" title="Dashboard">
                        <i class="fa fa-solid fa-house text-sm"></i>
                    </a>
                </div>
            </div>
        </x-card.header>

        <section class="p-4">
            <h4 class="font-semibold text-gray-800 mb-2">Tier List Settings</h4>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-4">
                <div class="my-2">
                    <label>Name the Tier List:</label>
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

    {{-- Tier list preview --}}
    @if ($tierlist->is_complete)
        <div class="bg-white rounded-lg shadow-md mt-4 p-4">
            <div class="flex justify-between items-center mb-4">
                <h6 class="font-semibold text-zinc-700">Tier List</h6>

                @if ($canEditEntries)
                    <span class="text-xs text-green-600 font-medium">
                        <i class="fa fa-solid fa-unlock mr-1"></i>
                        Pro &mdash; entries are editable
                    </span>
                @else
                    <span class="text-xs text-zinc-400">
                        <i class="fa fa-solid fa-lock mr-1"></i>
                        Read-only
                    </span>
                @endif
            </div>

            @if ($canEditEntries)
                <livewire:tierlist.tierlist-builder :tierlist="$tierlist" />
            @else
                {{-- Locked board for free users --}}
                <div class="relative" x-data="{ showTooltip: false }">
                    <div class="space-y-1 opacity-60">
                        @foreach ($tiers as $tier)
                            <div
                                class="flex items-stretch rounded-lg overflow-hidden border border-zinc-200"
                                wire:key="locked-tier-{{ $tier->getKey() }}"
                            >
                                <div
                                    class="w-16 sm:w-24 shrink-0 flex items-center justify-center p-2 text-zinc-800 font-bold"
                                    style="background-color: {{ $tier->color }}"
                                >
                                    {{ $tier->name }}
                                </div>

                                <div class="flex-1 flex flex-wrap gap-2 p-2 bg-zinc-50 min-h-20">
                                    @foreach ($tier->items as $item)
                                        @include('livewire.tierlist.partials.board-tile', ['item' => $item])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Lock overlay — scoped to the tier rows only --}}
                    <div
                        class="absolute inset-0 cursor-not-allowed flex items-center justify-center"
                        @mouseenter="showTooltip = true"
                        @mouseleave="showTooltip = false"
                    >
                        <div
                            x-show="showTooltip"
                            x-transition
                            class="bg-zinc-800 text-white text-xs rounded-lg px-3 py-2 shadow-lg"
                        >
                            <i class="fa fa-solid fa-lock mr-1"></i>
                            Upgrade to Song Rank Pro to edit your completed tier list
                        </div>
                    </div>
                </div>

                {{-- Upgrade banner --}}
                <x-pro-upgrade-banner class="mt-4">
                    <p class="mt-2 text-sm text-zinc-600">
                        Want to rearrange entries on a finished tier list? Go Pro for full editing.
                    </p>
                </x-pro-upgrade-banner>
            @endif
        </div>
    @endif
</div>
