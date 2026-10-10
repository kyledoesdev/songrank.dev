{{-- Livewire partial: the including component lists $this->crates through HasCrateList. --}}

@php($sortable = $this->cratesAreSortable())

<div
    wire:key="crate-grid-{{ $sortable ? 'custom' : 'alphabetical' }}"
    class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
    @if ($sortable) wire:sort="reorderCrate" @endif
>
    @foreach ($this->crates as $crate)
        <div wire:key="crate-{{ $crate->getKey() }}" @if ($sortable) wire:sort:item="{{ $crate->getKey() }}" @endif>
            <x-catalog.crate-tile :crate="$crate" :sortable="$sortable">
                <x-slot:actions>
                    <livewire:catalog.crate-settings
                        :crate="$crate"
                        class="h-8 w-8 rounded-lg flex items-center justify-center text-zinc-400 hover:text-zinc-700 hover:bg-zinc-100 transition-colors"
                        :wire:key="'crate-settings-'.$crate->getKey()"
                    />
                </x-slot:actions>
            </x-catalog.crate-tile>
        </div>
    @endforeach
</div>
