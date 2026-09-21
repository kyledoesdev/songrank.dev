<div class="w-full max-w-6xl mx-auto mt-4">
    <x-profile-card :user="$user" />

    @if ($hasRankings || $hasTierlists)
        <div x-data="{ tab: '{{ $hasRankings ? 'rankings' : 'tierlists' }}' }">
            {{-- Tab bar — only when both types have content --}}
            @if ($showTabs)
                <div class="bg-white rounded-lg shadow-md p-2 flex gap-2 mb-4">
                    <button
                        class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                        :class="tab === 'rankings' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                        @click="tab = 'rankings'"
                    >
                        <i class="fa-solid fa-trophy mr-1"></i>
                        Rankings
                        <span class="ml-1 text-xs text-zinc-400">({{ $this->rankings->count() }})</span>
                    </button>
                    <button
                        class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                        :class="tab === 'tierlists' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                        @click="tab = 'tierlists'"
                    >
                        <i class="fa-solid fa-table-cells-large mr-1"></i>
                        Tier Lists
                        <span class="ml-1 text-xs text-zinc-400">({{ $this->tierlists->count() }})</span>
                    </button>
                </div>
            @endif

            {{-- Rankings tab --}}
            <div x-show="tab === 'rankings'" x-cloak>
                <div class="{{ $this->rankings->count() > 1 ? 'grid grid-cols-1 md:grid-cols-2 gap-4 overflow-x-auto pt-2' : 'grid grid-cols-1 gap-4 overflow-x-auto pt-2' }}">
                    @foreach ($this->rankings as $ranking)
                        <livewire:ranking.card
                            :key="'ranking-card-'.$ranking->getKey()"
                            :ranking="$ranking"
                        />
                    @endforeach
                </div>
            </div>

            {{-- Tier Lists tab --}}
            @if ($hasTierlists)
                <div x-show="tab === 'tierlists'" x-cloak>
                    <div class="{{ $this->tierlists->count() > 1 ? 'grid grid-cols-1 md:grid-cols-2 gap-4 overflow-x-auto pt-2' : 'grid grid-cols-1 gap-4 overflow-x-auto pt-2' }}">
                        @foreach ($this->tierlists as $tierlist)
                            <x-tierlists.card :tierlist="$tierlist" :key="'tierlist-card-'.$tierlist->getKey()" />
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @else
        {{-- Empty state --}}
        <div class="col-span-full text-center py-8 text-gray-500 rounded-lg shadow-md bg-white">
            <p class="text-lg">This user hasn't created anything yet.</p>
            @if (auth()->id() === $user->getKey())
                <p class="text-sm mt-2">
                    <a href="{{ route('dashboard') }}" class="text-blue-500 hover:text-blue-700">
                        Go build something great! <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </p>
            @endif
        </div>
    @endif
</div>
