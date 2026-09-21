<div>
    <x-card>
        @include('livewire.song-rank.setup.partials.setup-header', [
            'type' => $this->rankingType(),
            'locked' => filled($selectedShow),
            'placeholder' => 'https://open.spotify.com/show/...',
        ])

        <div x-auto-animate.300ms>
            @if ($selectedShow)
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
                            'namePlaceholder' => $selectedShow['name'] . ' List',
                            'tracksToRankCount' => $this->rankableTracks()->count(),
                            'itemLabel' => $this->rankingType()->itemLabel(),
                        ])
                    </div>

                    <div class="md:col-span-2" x-auto-animate>
                        @include('livewire.song-rank.setup.partials.track-list', [
                            'tracks' => $selectedTracks,
                            'type' => $this->rankingType(),
                            'removed' => $removedTrackUuids,
                            'title' => ucfirst($this->rankingType()->itemLabel()),
                            'keyPrefix' => 'track',
                            'searchPlaceholder' => 'Search episodes...',
                        ])
                    </div>
                </div>
            @endif
        </div>
    </x-card>
</div>
