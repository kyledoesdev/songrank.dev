@props(['song', 'showEmbeds', 'wireKey'])

<div class="w-full overflow-x-hidden">
    <div class="w-full flex flex-col">
        @if ($showEmbeds)
            <div class="w-full overflow-hidden">
                <iframe
                    src="https://open.spotify.com/embed/{{ $song['is_podcast'] ? 'episode' : 'track' }}/{{ $song['spotify_song_id'] }}"
                    class="w-full max-w-full"
                    style="min-height: 232px;"
                    frameborder="0"
                    allowtransparency="true"
                    allow="encrypted-media"
                ></iframe>
            </div>
        @else
            <div class="mb-2 flex justify-center bg-gray-100 p-4 rounded-lg">
                <div class="text-center">
                    <img
                        src="{{ $song['cover'] }}"
                        alt="{{ $song['title'] }}"
                        class="w-48 h-48 sm:w-64 sm:h-64 object-cover rounded-lg shadow-lg mx-auto"
                    />
                    <div class="mt-2 font-medium text-gray-800">{{ $song['title'] }}</div>
                </div>
            </div>
        @endif

        <div class="mt-4 flex flex-col items-center">
            <hr class="w-full my-3">
            <button
                class="border-2 border-zinc-800 rounded-lg hover:bg-zinc-100 text-zinc-800 px-6 py-2 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed min-w-[200px]"
                wire:click="chooseSong({{ $song['id'] }})"
                wire:loading.attr="disabled"
                wire:target="chooseSong"
                wire:key="{{ $wireKey }}"
            >
                <span wire:loading.remove wire:target="chooseSong">{{ $song['title'] }}</span>
                <span wire:loading wire:target="chooseSong" class="inline-flex items-center gap-2">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                </span>
            </button>
        </div>
    </div>
</div>
