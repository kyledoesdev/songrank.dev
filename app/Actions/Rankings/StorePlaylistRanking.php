<?php

namespace App\Actions\Rankings;

use App\Actions\Playlists\ResolvePlaylist;
use App\Enums\RankingType;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Ranking;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StorePlaylistRanking
{
    public function handle(User $user, array $attributes): Ranking
    {
        return DB::transaction(function () use ($user, $attributes) {
            $playlist = (new ResolvePlaylist)->handle($attributes['playlist']);

            $name = $attributes['ranking_name'] === '' || is_null($attributes['ranking_name'])
                ? $playlist->name.' List'
                : $attributes['ranking_name'];

            $ranking = Ranking::create([
                'type' => RankingType::PLAYLIST->value,
                'source_id' => $playlist->getKey(),
                'user_id' => $user->getKey(),
                'name' => Str::limit($name, 30),
                'is_public' => $attributes['is_public'] ?? false,
                'comments_enabled' => $attributes['comments_enabled'] ?? false,
                'comments_replies_enabled' => $attributes['comments_replies_enabled'] ?? false,
            ]);

            $ranking->sortingState()->create();

            $tracks = collect($attributes['tracks']);

            $artists = $this->resolveArtists($tracks);

            // Map songs with artist IDs
            $songs = $tracks->map(fn ($track) => [
                'artist_id' => $artists->get($track['artist_id']),
                'ranking_id' => $ranking->getKey(),
                'spotify_song_id' => $track['id'],
                'uuid' => $track['uuid'],
                'title' => $track['name'] ?? 'Track deleted from spotify servers.',
                'cover' => $track['cover'] ?? 'https://i.imgur.com/MBDmIUg.png',
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            Song::insert($songs);

            return $ranking;
        });
    }

    /**
     * Make sure every artist the playlist references has a record.
     *
     * @param  Collection<int, array{artist_id: string, artist_name: string, is_podcast: bool}>  $tracks
     * @return Collection<string, int> spotify artist id => artists.id
     */
    private function resolveArtists(Collection $tracks): Collection
    {
        $trackArtists = $tracks->unique('artist_id')->values();

        $artists = Artist::query()
            ->whereIn('artist_id', $trackArtists->pluck('artist_id'))
            ->pluck('id', 'artist_id');

        /* Insert the ones we've never seen, in a single query. Upserting the whole set instead
           would burn an auto-increment id for every row that already existed. */
        $newArtists = $trackArtists->reject(fn (array $track) => $artists->has($track['artist_id']));

        if ($newArtists->isEmpty()) {
            return $artists;
        }

        Artist::insertOrIgnore($newArtists->map(fn (array $track) => [
            'artist_id' => $track['artist_id'],
            'artist_name' => $track['artist_name'],
            'is_podcast' => $track['is_podcast'],
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        return $artists->merge(
            Artist::query()
                ->whereIn('artist_id', $newArtists->pluck('artist_id'))
                ->pluck('id', 'artist_id')
        );
    }
}
