<?php

namespace App\Actions\Rankings;

use App\Actions\Artists\ResolveArtists;
use App\Enums\RankingType;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreArtistRanking
{
    public function handle(User $user, array $attributes): Ranking
    {
        return DB::transaction(function () use ($user, $attributes) {
            /* update or create the artist */
            $rankedArtist = Artist::updateOrCreate([
                'artist_id' => data_get($attributes, 'artist.id'),
            ], [
                'artist_name' => data_get($attributes, 'artist.name'),
                'artist_img' => data_get($attributes, 'artist.cover'),
            ]);

            $name = $attributes['ranking_name'] === '' || is_null($attributes['ranking_name'])
                ? $rankedArtist->artist_name.' List'
                : $attributes['ranking_name'];

            /* create a new ranking */
            $ranking = Ranking::create([
                'type' => RankingType::ARTIST->value,
                'source_id' => $rankedArtist->getKey(),
                'user_id' => $user->getKey(),
                'name' => Str::limit($name, 30),
                'is_public' => $attributes['is_public'] ?? false,
                'comments_enabled' => $attributes['comments_enabled'] ?? false,
                'comments_replies_enabled' => $attributes['comments_replies_enabled'] ?? false,
            ]);

            /* create the relation to the ranking's sorted state */
            $ranking->sortingState()->create();

            $tracks = collect($attributes['tracks']);

            /* "appears on" tracks belong to their primary artist, so those rows need
               to exist. Songs the ranked artist owns are not in here. */
            $featuredArtists = (new ResolveArtists)->handle(
                $tracks
                    ->filter(fn (array $song) => $song['featured_artist'] ?? false)
                    ->pluck('primary_artist')
                    ->filter()
            );

            $songs = $tracks->map(function ($song) use ($ranking, $rankedArtist, $featuredArtists) {
                $isFeaturedTrack = $song['featured_artist'] ?? false;

                return [
                    'ranking_id' => $ranking->getKey(),
                    'artist_id' => $isFeaturedTrack
                        ? $featuredArtists->get($song['primary_artist']['id'])
                        : $rankedArtist->getKey(),
                    'spotify_song_id' => $song['id'],
                    'uuid' => $song['uuid'],
                    'title' => $song['name'] ?? 'Track deleted from spotify servers.',
                    'cover' => $song['cover'] ?? 'https://i.imgur.com/MBDmIUg.png',
                    'featured_artist' => $isFeaturedTrack,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->toArray();

            /* batch insert the song records */
            Song::insert($songs);

            return $ranking;
        });
    }
}
