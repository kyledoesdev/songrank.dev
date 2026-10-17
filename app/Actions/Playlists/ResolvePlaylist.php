<?php

namespace App\Actions\Playlists;

use App\Models\Playlist;

final class ResolvePlaylist
{
    /**
     * The playlist row behind a Spotify playlist, from the payload
     * GetPlaylistTracks hands back.
     *
     * @param  array<string, mixed>  $playlist
     */
    public function handle(array $playlist): Playlist
    {
        return Playlist::updateOrCreate([
            'playlist_id' => data_get($playlist, 'id'),
        ], [
            'creator_id' => data_get($playlist, 'creator.id'),
            'creator_name' => data_get($playlist, 'creator.display_name'),
            'name' => data_get($playlist, 'name'),
            'description' => data_get($playlist, 'description'),
            'cover' => data_get($playlist, 'cover'),
            'track_count' => data_get($playlist, 'track_count'),
        ]);
    }
}
