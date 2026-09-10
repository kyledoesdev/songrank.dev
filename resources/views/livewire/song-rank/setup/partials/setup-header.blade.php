@use('App\Enums\RankingType')

{{-- Livewire partial: expects $type, $locked, and $placeholder from the including setup view. --}}

<div class="p-2 mb-2" x-auto-animate>
    @if ($this->rankingLimitReached())
        <div class="rounded-xl border border-purple-200 bg-purple-50 p-6 text-center">
            <i class="fa fa-solid fa-star text-2xl text-purple-400"></i>

            <h5 class="mt-3 font-semibold text-zinc-800">
                You've reached {{ $this->rankingAllowance()->limit() }} rankings
            </h5>

            <p class="mt-2 text-sm text-zinc-600 max-w-lg mx-auto">
                Free accounts can keep up to {{ $this->rankingAllowance()->limit() }} rankings. Delete one you're finished with, or go Pro for unlimited rankings of every type.
            </p>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                <a href="{{ route('billing') }}" class="btn-primary p-3">
                    <i class="fa fa-solid fa-star mr-1"></i>
                    Go Pro &mdash; $10 once
                </a>
                <a href="{{ route('dashboard') }}" class="text-sm px-3 py-2 rounded-lg bg-white border border-zinc-200 hover:border-zinc-300">
                    Manage your rankings
                </a>
            </div>
        </div>
    @else
    <div class="space-y-4 md:space-y-0">
        <h5 class="md:text-md mt-2 mb-4 md:text-left">
            Choose a ranking type to get started. Enter in an artist name, or the playlist or show URL.
        </h5>

        <div class="flex flex-col md:flex-row md:items-center md:gap-4">
            <div class="flex flex-wrap gap-2 mb-4 md:mb-0 md:shrink-0">
                @foreach (RankingType::cases() as $tab)
                    <button
                        type="button"
                        x-data
                        @if (! $locked && $tab !== $type)
                            @click="$dispatch('switch-ranking-type', { type: '{{ $tab->value }}' })"
                        @endif
                        @disabled($locked && $tab !== $type)
                        @class([
                            'flex items-center gap-2 px-4 py-2 rounded-full border-2 transition-all duration-300',
                            'shadow-md cursor-pointer' => $tab === $type,
                            'border-purple-500 bg-purple-100 text-purple-700' => $tab === $type && $tab === RankingType::ARTIST,
                            'border-green-500 bg-green-100 text-green-700' => $tab === $type && $tab === RankingType::PLAYLIST,
                            'border-blue-500 bg-blue-100 text-blue-700' => $tab === $type && $tab === RankingType::SHOW,
                            'border-zinc-200 bg-zinc-100 text-zinc-300 cursor-not-allowed opacity-50' => $locked && $tab !== $type,
                            'border-zinc-300 bg-white text-zinc-500 hover:border-zinc-400 cursor-pointer' => ! $locked && $tab !== $type,
                        ])
                    >
                        <i class="fa-solid {{ $tab->icon() }}"></i>
                        <span class="font-medium text-sm">{{ $tab->label() }}</span>
                    </button>
                @endforeach
            </div>

            <div class="flex-1 flex flex-col sm:flex-row items-center w-full space-y-2 sm:space-y-0 sm:space-x-2">
                <input
                    wire:key="search-input"
                    class="w-full sm:flex-1 p-2 border border-zinc-800 rounded-lg transition-all duration-300 focus:ring-2 focus:ring-blue-400"
                    type="text"
                    placeholder="{{ $placeholder }}"
                    wire:model="searchTerm"
                    wire:keydown.enter="search"
                />
                <div class="flex space-x-2">
                    <button
                        type="button"
                        class="btn-primary px-2 py-1 cursor-pointer transform transition-all duration-300 hover:scale-110 active:scale-95"
                        x-data
                        @click="window.showLoader(); $wire.search().then(() => window.hideLoader())"
                    >
                        <i class="text-lg text-zinc-800 fa fa-magnifying-glass mt-1"></i>
                    </button>
                    <button
                        type="button"
                        class="btn-secondary px-2 py-1 cursor-pointer transform transition-all duration-300 hover:scale-110 active:scale-95"
                        wire:click="resetSetup"
                    >
                        <i class="text-lg text-zinc-800 fa-solid fa-rotate-left mt-1"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
