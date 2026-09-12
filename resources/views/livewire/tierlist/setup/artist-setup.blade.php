<div>
    <div class="bg-white shadow-md rounded-xl">
        @include('livewire.tierlist.setup.partials.setup-header', [
            'type' => $this->tierlistType(),
            'placeholder' => $randomArtist,
        ])

        @unless ($this->tierlistLimitReached())
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 m-2 p-2 pt-4" x-auto-animate.300ms>
                <div class="md:col-span-1" x-auto-animate>
                    @include('livewire.tierlist.setup.partials.tierlist-form', [
                        'type' => $this->tierlistType(),
                        'namePlaceholder' => 'My Artist Tier List',
                    ])
                </div>

                <div class="md:col-span-2 grid grid-cols-1 lg:grid-cols-2 gap-4" x-auto-animate>
                    @include('livewire.tierlist.setup.partials.search-results', [
                        'title' => 'Search Results',
                    ])

                    @include('livewire.tierlist.setup.partials.entry-bank', [
                        'type' => $this->tierlistType(),
                    ])
                </div>
            </div>
        @endunless
    </div>
</div>
