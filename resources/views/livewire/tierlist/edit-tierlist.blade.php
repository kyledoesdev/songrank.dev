<div>
    <div class="bg-white rounded-lg shadow-md mt-4 space-y-6" style="min-height: 300px;">
        {{-- Header --}}
        <div class="flex justify-between mt-2">
            <div>
                <h5 class="md:text-xl mt-3 mx-3">Edit your tier list</h5>
                <p class="text-xs text-zinc-500 mx-3">
                    {{ $tierlist->type->label() }} tier list
                    <span class="text-zinc-300 mx-1">|</span>
                    {{ $tierlist->items->count() }} {{ $tierlist->type->itemLabel() }}
                </p>
            </div>
            <div class="mr-4 flex gap-2 items-start mt-3">
                <button
                    type="button"
                    class="btn-primary"
                    wire:click="update"
                >
                    <i class="fa fa-solid fa-check"></i>
                </button>
                <a
                    href="{{ route('tierlist', ['id' => $tierlist->getKey()]) }}"
                    class="btn-secondary"
                >
                    <i class="fa fa-solid fa-times"></i>
                </a>
                <button
                    type="button"
                    class="btn-danger"
                    wire:click="confirmDestroy"
                >
                    <i class="fa fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

        {{-- Metadata form --}}
        <div class="grid grid-cols-1 md:max-w-1/2 mx-3">
            <div class="mb-4">
                <label class="block mb-2">Tier List Name?</label>
                <input
                    type="text"
                    class="w-full bg-zinc-100 rounded-lg p-2"
                    wire:model.live.debounce.500ms="form.name"
                    maxlength="30"
                />
                @error('form.name')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="mb-4">
                <label class="block mb-2">Show In Explore Feed?</label>
                <select
                    class="w-full bg-zinc-100 rounded-lg p-2"
                    wire:model.live.debounce.500ms="form.is_public"
                    required
                >
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-2">Comments Enabled?</label>
                <select
                    class="w-full bg-zinc-100 rounded-lg p-2"
                    wire:model.live.debounce.500ms="form.comments_enabled"
                    required
                >
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>

            <div class="mb-4">
                <label class="block mb-2">Comment Replies Enabled?</label>
                <select
                    class="w-full bg-zinc-100 rounded-lg p-2"
                    wire:model.live.debounce.500ms="form.comments_replies_enabled"
                    required
                >
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
    </div>

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

                    {{-- Full-board lock overlay --}}
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

                    {{-- Upgrade banner --}}
                    <div class="mt-4 rounded-xl border border-purple-200 bg-purple-50 p-4 text-center">
                        <i class="fa fa-solid fa-star text-xl text-purple-400"></i>
                        <p class="mt-2 text-sm text-zinc-600">
                            Want to rearrange entries on a finished tier list? Go Pro for full editing.
                        </p>
                        <a href="{{ route('billing') }}" class="inline-block mt-3 btn-primary px-4 py-2 text-sm">
                            <i class="fa fa-solid fa-star mr-1"></i>
                            Go Pro &mdash; $10 once
                        </a>
                    </div>
                </div>
            @endif
        </div>
    @endif
</div>
