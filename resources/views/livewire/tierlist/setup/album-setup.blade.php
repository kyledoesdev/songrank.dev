<div>
    <x-card>
        @include('livewire.tierlist.setup.partials.setup-header', [
            'type' => $this->tierlistType(),
            'placeholder' => $mode === 'artist' ? 'Search an artist to import their discography...' : 'Search for an album...',
        ])

        @unless ($this->tierlistLimitReached())
            @include('livewire.tierlist.setup.partials.search-modes', [
                'modes' => [
                    'album' => 'Search albums',
                    'artist' => 'Import a discography',
                ],
            ])

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 m-2 p-2 pt-4" x-auto-animate.300ms>
                <div class="md:col-span-1" x-auto-animate>
                    @include('livewire.tierlist.setup.partials.tierlist-form', [
                        'type' => $this->tierlistType(),
                        'namePlaceholder' => $selectedArtist ? $selectedArtist['name'].' Tier List' : 'My Album Tier List',
                    ])
                </div>

                <div class="md:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4" x-auto-animate>
                    @if ($artistResults)
                        <div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
                            <x-card.header>
                                <h4 class="font-semibold text-gray-800">Pick an artist</h4>
                            </x-card.header>

                            <div class="card-scroller-half p-2" x-auto-animate>
                                @foreach ($artistResults as $artist)
                                    <div
                                        class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 transition-all duration-300"
                                        wire:key="artist-{{ $artist['id'] }}"
                                    >
                                        <img
                                            src="{{ $artist['cover'] }}"
                                            alt="{{ $artist['name'] }}"
                                            class="w-12 h-12 sm:w-14 sm:h-14 rounded-xl border border-zinc-200 object-cover shadow-sm shrink-0"
                                        >
                                        <div class="min-w-0 flex-1">
                                            <h5 class="text-sm sm:text-base truncate">{{ $artist['name'] }}</h5>
                                            <div class="mt-1">
                                                <x-spotify-logo :artist="$artist['id']" />
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            class="btn-primary px-2 py-1 shrink-0"
                                            x-data
                                            @click="window.showLoader(); $wire.loadDiscography('{{ $artist['id'] }}').then(() => window.hideLoader())"
                                            title="Load this discography"
                                        >
                                            <i class="fa-solid fa-compact-disc"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @else
                        @include('livewire.tierlist.setup.partials.search-results', array_filter([
                            'title' => $selectedArtist ? $selectedArtist['name'].' releases' : 'Search Results',
                            'bulkAction' => $selectedArtist ? 'addDiscography' : null,
                        ]))
                    @endif

                    @include('livewire.tierlist.setup.partials.entry-bank', [
                        'type' => $this->tierlistType(),
                    ])
                </div>
            </div>
        @endunless
    </x-card>
</div>
