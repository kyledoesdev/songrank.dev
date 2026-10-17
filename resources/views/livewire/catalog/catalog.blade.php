@use('App\Enums\CatalogSort')

<div class="mt-4">
    <x-card>
        <x-card.header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div class="min-w-0">
                    <h5 class="text-base sm:text-lg md:text-xl font-medium">
                        <i class="fa-solid fa-box-archive text-primary-icon mr-1"></i>
                        Catalog
                    </h5>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        Crates for your rankings, tier lists and reviews
                        <span class="text-zinc-300 mx-1">|</span>
                        @if (Auth::user()->crateLimit())
                            {{ Auth::user()->crateCount() }} of {{ Auth::user()->crateLimit() }} crates used
                        @else
                            {{ Auth::user()->crateCount() }} {{ Str::plural('crate', Auth::user()->crateCount()) }}
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    @if (Auth::user()->canArrangeCrates())
                        <div class="flex rounded-lg border border-zinc-200 bg-white overflow-hidden text-xs">
                            @foreach (CatalogSort::cases() as $sort)
                                <button
                                    type="button"
                                    wire:click="sortBy('{{ $sort->value }}')"
                                    @class([
                                        'px-3 py-1.5 cursor-pointer transition-colors',
                                        'bg-primary-soft text-primary font-medium' => Auth::user()->catalogSort() === $sort,
                                        'text-zinc-500 hover:text-zinc-800' => Auth::user()->catalogSort() !== $sort,
                                    ])
                                    title="{{ $sort === CatalogSort::CUSTOM ? 'Drag crates into your own order' : 'Sort crates by name' }}"
                                >
                                    {{ $sort->label() }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($this->canAddCrate())
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-lg border border-purple-300 bg-purple-100 hover:bg-purple-200 text-purple-700 font-medium text-xs px-3 py-1.5 cursor-pointer transition-colors"
                            wire:click="startCreatingCrate"
                        >
                            <i class="fa-solid fa-folder-plus"></i>
                            New crate
                        </button>
                    @endif
                </div>
            </div>
        </x-card.header>

        <div class="p-4">
            @if ($this->crates->isEmpty())
                <div class="text-center py-10">
                    <i class="fa-solid fa-box-archive text-3xl text-zinc-300 mb-3"></i>
                    <p class="text-sm text-zinc-500">
                        No crates yet. Make one for an artist, a year, a mood, whatever suits you.
                    </p>
                </div>
            @else
                @include('livewire.catalog.partials.crate-grid')
            @endif

            @unless (Auth::user()->canCreateCrate())
                <x-pro-upgrade-banner class="mt-4">
                    <h5 class="mt-3 font-semibold text-zinc-800">
                        You've used all your crates
                    </h5>

                    <p class="mt-2 text-sm text-zinc-600 max-w-lg mx-auto">
                        Free accounts can keep {{ Auth::user()->crateLimit() }} crates.
                        Delete one, or go Pro for as many as you like, with crates inside crates.
                    </p>
                </x-pro-upgrade-banner>
            @endunless
        </div>
    </x-card>

    @include('livewire.catalog.partials.new-crate-modal')
</div>
