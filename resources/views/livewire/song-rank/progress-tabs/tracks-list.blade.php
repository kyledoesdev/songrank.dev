<div class="p-4">
    <p class="text-sm text-gray-600 mb-4">{{ $songs->count() }} tracks in this ranking</p>

    <div class="space-y-2">
        @foreach($songs as $song)
            <x-songs.ranked-item
                :song="$song"
                :ranking="$ranking"
            />
        @endforeach
    </div>
</div>