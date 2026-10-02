@use('App\Enums\ReviewType')

<div>
    <div class="min-h-screen py-4">
        <div class="w-full max-w-6xl mx-auto" x-data="{ tab: 'rankings' }">
            {{-- Tab bar --}}
            <div class="bg-white rounded-lg shadow-md p-2 flex gap-2 mb-4">
                <button
                    class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                    :class="tab === 'rankings' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                    @click="tab = 'rankings'"
                >
                    <i class="fa-solid fa-trophy mr-1"></i>
                    Rankings
                </button>

                <button
                    class="px-4 py-2 rounded-lg text-sm font-medium cursor-pointer transition-colors"
                    :class="tab === 'reviews' ? 'bg-zinc-100 text-zinc-800' : 'text-zinc-500 hover:text-zinc-700 hover:bg-zinc-50'"
                    @click="tab = 'reviews'"
                >
                    <i class="fa-solid fa-pen-nib mr-1"></i>
                    Reviews
                </button>
            </div>

            <div x-show="tab === 'rankings'" class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                <x-leaderboards.section
                    title="Top Ranked Artists"
                    icon="fa-music"
                    :entries="$topArtists"
                    count-label="rankings"
                />

                <x-leaderboards.section
                    title="Top Creators"
                    icon="fa-user"
                    :entries="$topCreators"
                    count-label="rankings"
                />

                <x-leaderboards.section
                    title="Rankings with Most Songs"
                    icon="fa-list-ol"
                    :entries="$biggestRankings"
                    count-label="songs"
                />
            </div>

            <div x-show="tab === 'reviews'" x-cloak class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                <x-leaderboards.section
                    title="Top Reviewed Artists"
                    :icon="ReviewType::ARTIST->icon()"
                    :entries="$topReviewedArtists"
                    count-label="reviews"
                />

                <x-leaderboards.section
                    title="Top Reviewed Albums"
                    :icon="ReviewType::ALBUM->icon()"
                    :entries="$topReviewedAlbums"
                    count-label="reviews"
                />

                <x-leaderboards.section
                    title="Top Reviewed Tracks"
                    :icon="ReviewType::TRACK->icon()"
                    :entries="$topReviewedTracks"
                    count-label="reviews"
                />
            </div>
        </div>
    </div>
</div>
