@use('Laravel\Pennant\Feature')

<div class="mt-4 space-y-6">
    <div @class([
        'grid grid-cols-1 gap-6',
        'lg:grid-cols-2' => Feature::active('tierlists') !== Feature::active('reviews'),
        'lg:grid-cols-3' => Feature::active('tierlists') && Feature::active('reviews'),
    ])>
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