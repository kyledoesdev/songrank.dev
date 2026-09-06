<div>
    <div class="min-h-screen py-4">
        <div class="w-full max-w-6xl mx-auto">
            {{-- Header + Search --}}
            <div class="mb-4 bg-white rounded-lg shadow-sm p-4 border border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="whitespace-nowrap">
                        <h2 class="text-lg font-semibold text-zinc-800">
                            <i class="fa fa-compass text-primary-icon mr-1"></i>
                            Explore Rankings
                        </h2>
                        <p class="text-xs text-zinc-500 mt-0.5">
                            {{ number_format($totalRankings) }} completed {{ Str::plural('ranking', $totalRankings) }} to explore
                        </p>
                    </div>

                    <form wire:submit="performSearch" class="flex items-center gap-2 w-full sm:w-auto">
                        <div class="relative flex-1 sm:flex-none sm:w-80">
                            <i class="fa fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm"></i>
                            <input
                                class="w-full pl-9 pr-3 py-2 text-sm border border-zinc-300 rounded-full transition-all duration-300 focus:ring-2 focus:ring-purple-400 focus:border-purple-400 focus:outline-none"
                                type="text"
                                placeholder="Search by name, artist or playlist"
                                wire:model="search"
                            />
                        </div>

                        <button
                            type="submit"
                            class="btn-primary px-3 py-1.5 cursor-pointer transform transition-all duration-300 hover:scale-110 active:scale-95"
                            title="Search"
                        >
                            <i class="fa fa-magnifying-glass mt-1"></i>
                        </button>

                        <button
                            type="button"
                            class="btn-secondary px-3 py-1.5 cursor-pointer transform transition-all duration-300 hover:scale-110 active:scale-95"
                            wire:click="resetSearch"
                            title="Reset search"
                        >
                            <i class="fa-solid fa-rotate-left mt-1"></i>
                        </button>
                    </form>
                </div>
            </div>

            {{-- wire:intersect triggers loadMore on scroll, incrementing $perPage so the --}}
            {{-- full list re-renders with more results. Searching resets $perPage to 12. --}}
            @if ($this->rankings->count())
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach ($this->rankings as $ranking)
                        <div
                            class="rounded-md cursor-pointer p-1 transform transition-all duration-300 hover:scale-101"
                            wire:key="ranking-{{ $ranking->getKey() }}"
                            wire:transition
                        >
                            <x-rankings.card :ranking="$ranking" />
                        </div>
                    @endforeach
                </div>

                @if ($this->hasMorePages && ! $this->isFiltered)
                    <div wire:intersect="loadMore" class="mt-4">
                        <div class="animate-pulse grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach (range(1, 4) as $_)
                                <x-rankings.card-placeholder wire:transition />
                            @endforeach
                        </div>
                    </div>
                @endif
            @else
                <div class="flex justify-center">
                    <span>No rankings found.</span>
                </div>
            @endif
        </div>
    </div>
</div>
