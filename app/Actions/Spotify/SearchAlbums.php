<?php

namespace App\Actions\Spotify;

use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

final class SearchAlbums
{
    /**
     * Building a bank is a multi-add flow, unlike picking one artist to rank, so
     * it returns a deeper page of results than SearchArtists does.
     */
    public function handle(User $user, string $searchTerm): ?Collection
    {
        $success = (new RefreshToken)->handle($user);

        if (! $success) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$user->external_token,
                'Content-Type' => 'application/json',
            ])->get('https://api.spotify.com/v1/search', [
                'q' => $searchTerm,
                'type' => 'album',
                'limit' => 20,
            ]);

            $albums = collect();

            collect($response->json('albums.items'))->each(function ($album) use ($albums) {
                $cover = data_get($album, 'images.0.url');

                /* A coverless tile is a blank square on the board. */
                if (blank($cover)) {
                    return;
                }

                $albums->push([
                    'id' => $album['id'],
                    'name' => (string) $album['name'],
                    'cover' => $cover,
                    'artist_id' => data_get($album, 'artists.0.id'),
                    'artist_name' => data_get($album, 'artists.0.name'),
                    'release_date' => data_get($album, 'release_date'),
                    'total_tracks' => data_get($album, 'total_tracks'),
                    'album_type' => data_get($album, 'album_type'),
                ]);
            });
        } catch (Exception $e) {
            report($e);

            return null;
        }

        return $albums;
    }
}
