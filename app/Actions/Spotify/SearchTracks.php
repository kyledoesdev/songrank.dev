<?php

namespace App\Actions\Spotify;

use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

final class SearchTracks
{
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
                'type' => 'track',
                'limit' => 20,
            ]);

            $tracks = collect();

            collect($response->json('tracks.items'))->each(function ($track) use ($tracks) {
                $cover = data_get($track, 'album.images.0.url');

                if (blank($cover)) {
                    return;
                }

                $tracks->push([
                    'id' => $track['id'],
                    'name' => (string) $track['name'],
                    'cover' => $cover,
                    'artist_id' => data_get($track, 'artists.0.id'),
                    'artist_name' => data_get($track, 'artists.0.name'),
                    'album_name' => data_get($track, 'album.name'),
                ]);
            });
        } catch (Exception $e) {
            report($e);

            return null;
        }

        return $tracks;
    }
}
