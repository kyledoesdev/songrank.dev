<?php

namespace App\Actions\Artists;

use App\Models\Artist;
use Illuminate\Support\Collection;

final class ResolveArtists
{
    /**
     * Hands back the artist row id behind each Spotify artist id, creating the
     * ones we have never seen.
     *
     * @param  Collection<int, array{id: ?string, name: ?string}>  $artists
     * @return Collection<string, int> spotify artist id => artists.id
     */
    public function handle(Collection $artists): Collection
    {
        $artists = $artists
            ->filter(fn (array $artist) => filled($artist['id'] ?? null))
            ->unique('id')
            ->values();

        if ($artists->isEmpty()) {
            return collect();
        }

        $resolved = Artist::query()
            ->whereIn('artist_id', $artists->pluck('id'))
            ->pluck('id', 'artist_id');

        /* Insert the ones we've never seen, in a single query. Upserting the whole
           set instead would burn an auto-increment id for every row that already existed. */
        $unknown = $artists->reject(fn (array $artist) => $resolved->has($artist['id']));

        if ($unknown->isEmpty()) {
            return $resolved;
        }

        Artist::insertOrIgnore($unknown->map(fn (array $artist) => [
            'artist_id' => $artist['id'],
            'artist_name' => $artist['name'] ?? 'Unknown Artist',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        return $resolved->merge(
            Artist::query()
                ->whereIn('artist_id', $unknown->pluck('id'))
                ->pluck('id', 'artist_id')
        );
    }
}
