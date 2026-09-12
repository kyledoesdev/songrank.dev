<div class="mt-4 space-y-6">
    <livewire:SongRank.song-rank-setup />

    @feature('tierlists')
        <livewire:Tierlist.tierlist-panel />
    @endfeature

    <livewire:Dashboard.in-progress />
</div>