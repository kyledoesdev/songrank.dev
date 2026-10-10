<x-card x-data="{ tab: '{{ $this->hasAnything() ? 'in-progress' : 'catalog' }}' }">
    <x-card.header>
        <div class="flex items-center gap-2">
            <button
                type="button"
                x-on:click="tab = 'catalog'"
                :class="tab === 'catalog'
                    ? 'bg-purple-100 border-purple-400 text-purple-700'
                    : 'bg-gray-100 border-transparent text-zinc-600 hover:text-zinc-800'"
                class="text-xs font-medium px-3 py-1.5 rounded-lg border-b-2 transition-all cursor-pointer"
            >
                <i class="fa-solid fa-book mr-1"></i>
                Catalog
            </button>

            @if ($this->hasAnything())
                <button
                    type="button"
                    x-on:click="tab = 'in-progress'"
                    :class="tab === 'in-progress'
                        ? 'bg-purple-100 border-purple-400 text-purple-700'
                        : 'bg-gray-100 border-transparent text-zinc-600 hover:text-zinc-800'"
                    class="text-xs font-medium px-3 py-1.5 rounded-lg border-b-2 transition-all cursor-pointer"
                >
                    <i class="fa-solid fa-clock-rotate-left mr-1"></i>
                    Pick up where you left off
                </button>
            @endif
        </div>
    </x-card.header>

    {{-- Catalog tab --}}
    <div x-show="tab === 'catalog'" class="p-4">
        @if ($this->crates->isEmpty())
            <div class="text-center py-8">
                <i class="fa-solid fa-book text-3xl text-zinc-300 mb-3"></i>
                <p class="text-sm text-zinc-500 mb-4">
                    Crates keep your rankings, tier lists and reviews together, sorted however you like.
                </p>
                <a href="{{ route('catalog') }}" class="btn-primary px-3 py-1.5">
                    <i class="fa-solid fa-folder-plus mr-1"></i>
                    Make your first crate
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach ($this->crates as $crate)
                    <x-catalog.crate-tile :crate="$crate" wire:key="dashboard-crate-{{ $crate->getKey() }}" />
                @endforeach
            </div>
        @endif
    </div>

    @if ($this->crates->isNotEmpty())
        <x-card.footer x-show="tab === 'catalog'" class="text-right">
            <a href="{{ route('catalog') }}" class="text-xs text-primary hover:underline">
                View catalog
                <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </x-card.footer>
    @endif

    {{-- In-progress tab --}}
    @if ($this->hasAnything())
        <div x-show="tab === 'in-progress'" class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-auto-animate>
                @foreach ($this->items as $item)
                    @php [$card, $props] = $this->card($item); @endphp

                    @livewire($card, $props, key($card.'-'.$item->getKey()))
                @endforeach
            </div>
        </div>

        @if ($this->items->hasPages())
            <x-card.footer x-show="tab === 'in-progress'">
                {{ $this->items->links('pagination.minimal') }}
            </x-card.footer>
        @endif
    @endif
</x-card>
