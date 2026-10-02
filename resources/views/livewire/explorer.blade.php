<div>
    <div class="min-h-screen py-4">
        <div class="w-full max-w-6xl mx-auto">
            @if ($showTierlists || $showReviews)
                <div x-data="{ tab: 'rankings' }">
                    {{-- Tab bar --}}
                    <div class="bg-white rounded-lg shadow-md p-2 flex gap-2 mb-4">
                        <button
                            class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                            :class="tab === 'rankings' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                            @click="tab = 'rankings'"
                        >
                            <i class="fa-solid fa-trophy mr-1"></i>
                            Rankings
                        </button>
                        @if ($showTierlists)
                            <button
                                class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                                :class="tab === 'tierlists' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                                @click="tab = 'tierlists'"
                            >
                                <i class="fa-solid fa-table-cells-large mr-1"></i>
                                Tier Lists
                            </button>
                        @endif

                        @if ($showReviews)
                            <button
                                class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                                :class="tab === 'reviews' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                                @click="tab = 'reviews'"
                            >
                                <i class="fa-solid fa-pen-nib mr-1"></i>
                                Reviews
                            </button>
                        @endif
                    </div>

                    <div x-show="tab === 'rankings'" x-cloak>
                        <livewire:explorer.rankings-feed />
                    </div>

                    @if ($showTierlists)
                        <div x-show="tab === 'tierlists'" x-cloak>
                            <livewire:explorer.tierlists-feed />
                        </div>
                    @endif

                    @if ($showReviews)
                        <div x-show="tab === 'reviews'" x-cloak>
                            <livewire:explorer.reviews-feed />
                        </div>
                    @endif
                </div>
            @else
                <livewire:explorer.rankings-feed />
            @endif
        </div>
    </div>
</div>
