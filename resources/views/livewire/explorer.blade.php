<div>
    <div class="min-h-screen py-4">
        <div class="w-full max-w-6xl mx-auto">
            @if ($showTierlists)
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
                        <button
                            class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                            :class="tab === 'tierlists' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                            @click="tab = 'tierlists'"
                        >
                            <i class="fa-solid fa-table-cells-large mr-1"></i>
                            Tier Lists
                        </button>
                    </div>

                    <div x-show="tab === 'rankings'" x-cloak>
                        <livewire:explorer.rankings-feed />
                    </div>

                    <div x-show="tab === 'tierlists'" x-cloak>
                        <livewire:explorer.tierlists-feed />
                    </div>
                </div>
            @else
                <livewire:explorer.rankings-feed />
            @endif
        </div>
    </div>
</div>
