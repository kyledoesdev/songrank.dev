<div>
    <x-card>
        @include('livewire.song-rank.setup.partials.setup-header', [
            'type' => $this->rankingType(),
            'locked' => filled($selectedArtist),
            'placeholder' => $randomArtist,
        ])

        {{-- Main content area with auto-animate for smooth transitions between states --}}
        <div x-auto-animate.300ms>
            @if ($searchedArtists && ! $selectedArtist)
                <div class="grid grid-cols-2 md:grid-cols-6 gap-2 md:gap-0" x-auto-animate>
                    @foreach ($searchedArtists as $artist)
                        <div
                            :key="'artist-'.$artist['id']"
                            class="m-2 p-2"
                            x-data="{ show: false }"
                            x-init="setTimeout(() => show = true, {{ $loop->index * 50 }})"
                            x-show="show"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 transform scale-75 translate-y-4"
                            x-transition:enter-end="opacity-100 transform scale-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100 transform scale-100"
                            x-transition:leave-end="opacity-0 transform scale-75"
                        >
                            <img
                                class="shadow-md rounded-4xl m-2 cursor-pointer transform transition-all duration-300 hover:scale-105 hover:shadow-lg"
                                src="{{ $artist['cover'] }}"
                                alt="{{ $artist['name'] }}"
                                x-data
                                @click="window.showLoader(); $wire.loadArtistSongs('{{ $artist['id'] }}').then(() => window.hideLoader())"
                            >
                            <h5 class="mt-1 transition-all duration-300">{{ $artist['name'] }}</h5>
                            <div>
                                <x-spotify-logo :artist="$artist['id']" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($selectedArtist)
                <div
                    class="grid grid-cols-1 md:grid-cols-3 gap-8 m-2 p-2 pt-4"
                    x-data="{ show: false }"
                    x-init="show = true"
                    x-show="show"
                    x-transition:enter="transition ease-out duration-500"
                    x-transition:enter-start="opacity-0 transform translate-x-4"
                    x-transition:enter-end="opacity-100 transform translate-x-0"
                >
                    <div class="md:col-span-1" x-auto-animate>
                        @include('livewire.song-rank.setup.partials.ranking-form', [
                            'namePlaceholder' => $selectedArtist['name'] . ' List',
                            'tracksToRankCount' => $this->rankableTracks()->count(),
                            'itemLabel' => $this->rankingType()->itemLabel(),
                        ])
                    </div>

                    <div class="md:col-span-2 space-y-6" x-auto-animate>
                        @include('livewire.song-rank.setup.partials.track-list', [
                            'tracks' => $selectedTracks,
                            'type' => $this->rankingType(),
                            'removed' => $removedTrackUuids,
                            'title' => ucfirst($this->rankingType()->itemLabel()),
                            'keyPrefix' => 'track',
                            'scroller' => 'card-scroller-half',
                            'albums' => $this->albums(),
                        ])

                        @if ($appearsOnCount > 0)
                            <div x-auto-animate>
                                @if ($includeFeaturedTracks && $this->hasFeaturedTracks())
                                    @include('livewire.song-rank.setup.partials.track-list', [
                                        'tracks' => $featuredTracks,
                                        'type' => $this->rankingType(),
                                        'removed' => $removedTrackUuids,
                                        'title' => 'Featured On',
                                        'toggle' => 'includeFeaturedTracks',
                                        'keyPrefix' => 'featured',
                                        'searchPlaceholder' => 'Search featured tracks...',
                                        'scroller' => 'card-scroller-half',
                                    ])
                                @else
                                    <div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
                                        <x-card.header class="flex items-center gap-3">
                                            <h4 class="font-semibold text-gray-800">Featured On</h4>

                                            <x-toggle-switch
                                                wire:model.live="includeFeaturedTracks"
                                                x-data
                                                x-on:change="window.showLoader()"
                                            >
                                                Include
                                            </x-toggle-switch>
                                        </x-card.header>

                                        <p class="p-4 text-sm text-zinc-500">
                                            {{ $selectedArtist['name'] }} appears on other artists' releases.
                                            Turn on "Include" to load the tracks they're featured on.
                                        </p>
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </x-card>
</div>
