<div x-data="{ open: false }">
    <button type="button" {{ $attributes->class(['cursor-pointer']) }} x-on:click="open = true" title="Add to crates">
        @if ($entity)
            <i class="fa-solid fa-box-archive text-sm"></i>
        @else
            <i class="fa-solid fa-box-archive text-zinc-500 mr-1"></i>
            @if (count($crateIds))
                In {{ count($crateIds) }} {{ Str::plural('crate', count($crateIds)) }}
            @else
                Choose crates
            @endif
        @endif
    </button>

    @teleport('body')
        <div
            x-show="open"
            x-cloak
            x-transition.opacity
            x-on:keydown.escape.window="open = false"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div class="absolute inset-0 bg-black/50" x-on:click="open = false"></div>

            <x-card class="relative w-full max-w-md flex flex-col max-h-[85vh]">
                <x-card.header class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <h5 class="font-semibold text-zinc-800">Add to Crates</h5>
                    </div>

                    <button type="button" class="text-zinc-400 hover:text-zinc-700 cursor-pointer" x-on:click="open = false" title="Close">
                        <i class="fa-solid fa-xmark text-lg"></i>
                    </button>
                </x-card.header>

                <div class="p-4 overflow-y-auto flex-1">
                    @if ($this->crates->isEmpty())
                        <p class="text-sm text-zinc-500 text-center py-6">No crates yet. Make your first one below.</p>
                    @else
                        @include('livewire.catalog.partials.crate-picker-branch', ['crates' => $this->childrenOf()])
                    @endif
                </div>

                <x-card.footer class="py-3">
                    @if (auth()->user()->canCreateCrate())
                        <form wire:submit="createCrate" class="flex gap-2">
                            <input
                                type="text"
                                class="flex-1 min-w-0 bg-white border border-zinc-200 rounded-lg px-2 py-1.5 text-sm focus:ring-2 focus:ring-blue-400"
                                wire:model="newCrateName"
                                maxlength="40"
                                placeholder="New crate"
                            />

                            <button type="submit" class="btn-secondary my-0 px-2 py-1" title="New crate">
                                <i class="fa-solid fa-folder-plus text-sm"></i>
                            </button>
                        </form>

                        @error('newCrateName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                    @else
                        <p class="text-xs text-zinc-500">
                            That is all your crates used.
                            <a href="{{ route('billing') }}" class="text-primary hover:underline">Go Pro</a>
                            for as many as you like.
                        </p>
                    @endif
                </x-card.footer>
            </x-card>
        </div>
    @endteleport
</div>
