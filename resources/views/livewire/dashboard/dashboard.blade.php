<div class="mt-4 space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <livewire:SongRank.ranking-panel />

        @feature('tierlists')
            <livewire:Tierlist.tierlist-panel />
        @endfeature

        @feature('reviews')
            <livewire:Reviews.review-panel />
        @endfeature
    </div>

    <livewire:Dashboard.in-progress />
</div>