<div>
    <button
        type="button"
        id="crate-settings-{{ $this->getId() }}"
        {{ $attributes->class(['cursor-pointer', 'ring-2 ring-purple-300' => $open]) }}
        @unless ($open) wire:click="openSettings" @endunless
        title="Crate settings"
    >
        <i class="fa-solid fa-gear text-sm"></i>
    </button>

    {{-- Teleported, because the tile it opens from clips anything that spills out of it. --}}
    @if ($open)
        @teleport('body')
            <div
                x-anchor.bottom-end.offset.8="document.getElementById('crate-settings-{{ $this->getId() }}')"
                x-on:click.outside="$wire.close()"
                x-on:keydown.escape.window="$wire.close()"
                class="z-50 w-80 max-w-[calc(100vw-2rem)]"
            >
                <x-card class="border border-zinc-200 shadow-xl">
                    <x-card.header class="flex items-center justify-between">
                        <h5 class="font-semibold text-zinc-800 text-sm">Crate Settings</h5>

                        <button type="button" class="text-zinc-400 hover:text-zinc-700 cursor-pointer" wire:click="close" title="Close">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </x-card.header>

                    <div class="p-4 space-y-4 max-h-[70vh] overflow-y-auto">
                        <div>
                            <label class="text-sm text-zinc-600" for="crate-name-{{ $this->getId() }}">Name</label>
                            <input
                                id="crate-name-{{ $this->getId() }}"
                                type="text"
                                class="w-full bg-zinc-100 rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-400"
                                wire:model="form.name"
                                wire:keydown.enter="save"
                                maxlength="40"
                            />
                            @error('form.name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="text-sm text-zinc-600" for="crate-color-{{ $this->getId() }}">Color</label>
                            <input
                                id="crate-color-{{ $this->getId() }}"
                                type="color"
                                class="w-full h-10 rounded-lg cursor-pointer mt-1"
                                wire:model="form.color"
                            />
                            @error('form.color') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <p class="text-sm text-zinc-600">Icon</p>

                            <div class="grid grid-cols-7 gap-1 mt-1">
                                @foreach ($this->icons as $icon)
                                    @if ($this->iconIsLocked($icon))
                                        <span
                                            class="relative h-9 rounded-lg flex items-center justify-center bg-zinc-50 text-zinc-300 cursor-not-allowed"
                                            title="{{ $icon->name }} is part of Pro"
                                        >
                                            <i class="fa-solid {{ $icon->icon }}"></i>
                                            <i class="fa-solid fa-lock absolute bottom-0.5 right-0.5 text-[8px] text-purple-400"></i>
                                        </span>
                                    @else
                                        <button
                                            type="button"
                                            class="h-9 rounded-lg flex items-center justify-center cursor-pointer transition-colors"
                                            :class="$wire.form.crate_icon_id === {{ $icon->getKey() }} ? 'bg-primary-soft text-primary ring-2 ring-purple-300' : 'text-zinc-500 hover:bg-zinc-100'"
                                            x-on:click="$wire.form.crate_icon_id = {{ $icon->getKey() }}"
                                            title="{{ $icon->name }}"
                                        >
                                            <i class="fa-solid {{ $icon->icon }}"></i>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        @if (Auth::user()->canArrangeCrates())
                            <div>
                                <label class="text-sm text-zinc-600" for="crate-destination-{{ $this->getId() }}">Move to</label>

                                @if ($crate->isTopLevel() && $this->destinations->isEmpty())
                                    <p class="text-xs text-zinc-500 mt-1">
                                        Nowhere for it to go. A crate holding crates stays at the top level.
                                    </p>
                                @else
                                    <div class="flex gap-2 mt-1">
                                        <select
                                            id="crate-destination-{{ $this->getId() }}"
                                            class="flex-1 min-w-0 bg-zinc-100 rounded-lg p-2 text-sm focus:ring-2 focus:ring-blue-400"
                                            wire:model="destination"
                                        >
                                            <option value="">Choose where&hellip;</option>

                                            @unless ($crate->isTopLevel())
                                                <option value="top">Top level</option>
                                            @endunless

                                            @foreach ($this->destinations as $destination)
                                                <option value="{{ $destination->getKey() }}">Inside {{ $destination->name }}</option>
                                            @endforeach
                                        </select>

                                        <button type="button" class="btn-secondary px-3 py-1.5 text-sm" wire:click="move">
                                            Move
                                        </button>
                                    </div>
                                @endif

                                @error('destination') <p class="text-xs text-red-600 mt-1">Choose where it should go.</p> @enderror
                            </div>
                        @endif
                    </div>

                    <x-card.footer class="flex items-center justify-between">
                        <button type="button" class="btn-danger px-3 py-1.5 text-sm" wire:click="confirmDestroy">
                            <i class="fa-solid fa-trash-can mr-1"></i>
                            Delete
                        </button>

                        <button type="button" class="btn-primary px-3 py-1.5 text-sm" wire:click="save">
                            <i class="fa-solid fa-check mr-1"></i>
                            Save
                        </button>
                    </x-card.footer>
                </x-card>
            </div>
        @endteleport
    @endif
</div>
