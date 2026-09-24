<?php

namespace App\Actions\Spotify;

use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

final class GetArtistAlbums
{
    /**
     * An artist's discography, for seeding an album tier list from one place
     * rather than searching a record at a time.
     *
     * Singles come back alongside albums and carry their `album_type`, so the
     * setup screen can filter them out without a second round trip.
     */
    public function handle(User $user, string $artistId): ?Collection
    {
        $success = (new RefreshToken)->handle($user);

        if (! $success) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$user->external_token,
                'Content-Type' => 'application/json',
            ])->get("https://api.spotify.com/v1/artists/{$artistId}/albums", [
                'include_groups' => 'album,single',
                'limit' => 50,
            ]);

            $total = $response->json('total');
            $albums = collect();
            $offset = 0;

            for ($i = 0; $i < ceil($total / 50); $i++) {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer '.$user->external_token,
                    'Content-Type' => 'application/json',
                ])->get("https://api.spotify.com/v1/artists/{$artistId}/albums", [
                    'include_groups' => 'album,single',
                    'limit' => 50,
                    'offset' => $offset,
                ]);

                collect($response->json('items'))->each(function ($album) use ($albums) {
                    $cover = data_get($album, 'images.0.url');

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

                $offset += 50;
            }

            /* Spotify lists the same record once per market it was released in,
               so a discography arrives full of duplicates under one name. */
            $albums = $albums->groupBy(fn (array $album) => Str::lower($album['name']))->map->first()->values();
        } catch (Exception $e) {
            report($e);

            return null;
        }

        return $albums;
    }
}
