@props(['ranking', 'song'])

<div class="m-2 mb-2">
    <div class="flex">
        <div>
            <img
                src="{{ $song->cover }}"
                alt="{{ $song->title }}"
                class="w-12 h-12 sm:w-[72px] sm:h-[72px] rounded-xl mr-4"
            />
        </div>
        <div class="flex flex-1">
            <div class="min-w-0 pt-1 flex-1">
                <h5 class="mx-2 mt-1 mb-0.5 text-xs sm:text-base break-words" title="{{ $song->title }}">
                    @if ($ranking->type !== \App\Enums\RankingType::ARTIST || $song->featured_artist)
                        <span class="font-bold">{{ $song->artist?->artist_name }}</span> -
                    @endif
                    {{ $song->title }}
                </h5>
                @if ($song->featured_artist)
                    <p class="mx-2 mb-0.5 text-[10px] sm:text-xs text-zinc-500">
                        (feat. {{ $ranking->source?->name() }})
                    </p>
                @endif
                @unless ($ranking->isShowType())
                    <div class="flex items-center mx-2">
                        <x-spotify-logo :url="'https://open.spotify.com/' . ($song->artist?->is_podcast ? 'episode' : 'track') . '/' . $song->spotify_song_id" />
                    </div>
                @endunless
            </div>
        </div>
    </div>
</div>
