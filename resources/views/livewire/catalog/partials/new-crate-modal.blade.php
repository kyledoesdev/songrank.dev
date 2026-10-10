{{-- Livewire partial: the including component creates crates through HasCrateList, and passes $parentName when they nest. --}}

@if ($creatingCrate)
    <div
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        x-data
        x-on:keydown.escape.window="$wire.cancelCreatingCrate()"
        wire:key="new-crate"
    >
        <div class="absolute inset-0 bg-black/50" wire:click="cancelCreatingCrate"></div>

        <x-card class="relative w-full max-w-sm">
            <x-card.header>
                <h5 class="font-semibold text-zinc-800">New Crate</h5>
                @isset($parentName)
                    <p class="text-xs text-zinc-500 mt-0.5">Inside {{ $parentName }}</p>
                @endisset
            </x-card.header>

            <form wire:submit="createCrate" class="p-4">
                <label class="text-sm text-zinc-600" for="new-crate-name">Name</label>
                <input
                    id="new-crate-name"
                    type="text"
                    class="w-full bg-zinc-100 rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-400"
                    wire:model="newCrateName"
                    maxlength="40"
                    placeholder="{{ $randomArtist }}"
                    autofocus
                />
                @error('newCrateName') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </form>

            <x-card.footer class="flex justify-end gap-2">
                <button type="button" class="btn-secondary px-3 py-1.5 text-sm" wire:click="cancelCreatingCrate" title="Cancel">
                    <i class="fa-solid fa-xmark"></i>
                </button>
                <button type="button" class="btn-primary px-3 py-1.5 text-sm" wire:click="createCrate">
                    <i class="fa-solid fa-check mr-1"></i>
                    Create
                </button>
            </x-card.footer>
        </x-card>
    </div>
@endif
