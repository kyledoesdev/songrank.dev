<div>
    <div class="bg-white shadow-md rounded-xl">
        @include('livewire.tierlist.setup.partials.setup-header', [
            'type' => $this->tierlistType(),
            'placeholder' => $mode === 'playlist' ? 'https://open.spotify.com/playlist/...' : 'Search for a track...',
        ])

        @unless ($this->tierlistLimitReached())
            @include('livewire.tierlist.setup.partials.search-modes', [
                'modes' => [
                    'track' => 'Search tracks',
                    'playlist' => 'Import a playlist',
                ],
            ])

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 m-2 p-2 pt-4" x-auto-animate.300ms>
                <div class="md:col-span-1" x-auto-animate>
                    @include('livewire.tierlist.setup.partials.tierlist-form', [
                        'type' => $this->tierlistType(),
                        'namePlaceholder' => filled($selectedPlaylist) ? $selectedPlaylist['name'].' Tier List' : 'My Track Tier List',
                    ])
                </div>

                <div class="md:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4" x-auto-animate>
                    @if ($mode === 'playlist')
                        <div class="border border-gray-200 bg-white rounded-lg overflow-hidden">
                            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                                <h4 class="font-semibold text-gray-800">Playlist</h4>
                            </div>

                            <div class="p-4" x-auto-animate>
                                @if (filled($selectedPlaylist))
                                    <div class="flex items-center gap-3">
                                        <img
                                            src="{{ $selectedPlaylist['cover'] }}"
                                            alt="{{ $selectedPlaylist['name'] }}"
                                            class="w-14 h-14 rounded-xl border border-zinc-200 object-cover shadow-sm shrink-0"
                                        >
                                        <div class="min-w-0">
                                            <h5 class="text-sm sm:text-base truncate">{{ $selectedPlaylist['name'] }}</h5>
                                            <p class="text-xs text-zinc-500">
                                                {{ $selectedPlaylist['track_count'] }} tracks
                                            </p>
                                            <div class="mt-1">
                                                <x-spotify-logo :playlist="$selectedPlaylist['id']" />
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-zinc-500">
                                        Paste a public Spotify playlist URL above and its tracks go straight onto the board.
                                    </p>
                                @endif
                            </div>
                        </div>
                    @else
                        @include('livewire.tierlist.setup.partials.search-results', [
                            'title' => 'Search Results',
                        ])
                    @endif

                    @include('livewire.tierlist.setup.partials.entry-bank', [
                        'type' => $this->tierlistType(),
                    ])
                </div>
            </div>
        @endunless
    </div>
</div>
