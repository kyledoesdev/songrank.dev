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
    <div x-show="tab === 'catalog'" class="p-6">
        <div class="text-center py-8">
            <i class="fa-solid fa-book text-3xl text-zinc-300 mb-3"></i>
            <p class="text-sm text-zinc-500">
                Your catalog is coming soon. This is where you'll find all of your completed rankings and tier lists in one place.
            </p>
        </div>
    </div>

    {{-- In-progress tab --}}
    @if ($this->hasAnything())
        <div x-show="tab === 'in-progress'" class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" x-auto-animate>
                @foreach ($this->rankings as $ranking)
                    <div class="border rounded-xl" wire:key="ranking-{{ $ranking->getKey() }}">
                        <livewire:ranking.card :ranking="$ranking" :key="'ranking-card-'.$ranking->getKey()" />
                    </div>
                @endforeach

                @foreach ($this->tierlists as $tierlist)
                    @include('livewire.tierlist.partials.tierlist-row', ['tierlist' => $tierlist])
                @endforeach
            </div>
        </div>
    @endif
</x-card>
