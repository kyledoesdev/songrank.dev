<?php

namespace App\Actions\Rankings;

use App\Actions\MaskProfanity;
use App\Enums\RankingType;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Show;
use App\Models\Song;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class StoreShowRanking
{
    public function handle(User $user, array $attributes): Ranking
    {
        return DB::transaction(function () use ($user, $attributes) {
            $show = Show::updateOrCreate([
                'show_id' => data_get($attributes, 'show.id'),
            ], [
                'publisher' => data_get($attributes, 'show.publisher'),
                'name' => data_get($attributes, 'show.name'),
                'description' => data_get($attributes, 'show.description'),
                'cover' => data_get($attributes, 'show.cover'),
                'episode_count' => data_get($attributes, 'show.episode_count'),
                'data' => data_get($attributes, 'show.data'),
            ]);

            $artist = Artist::updateOrCreate([
                'artist_id' => data_get($attributes, 'show.id'),
            ], [
                'artist_name' => data_get($attributes, 'show.publisher'),
                'artist_img' => data_get($attributes, 'show.publisher_image'),
                'is_podcast' => true,
            ]);

            $name = $attributes['ranking_name'] === '' || is_null($attributes['ranking_name'])
                ? $show->name.' List'
                : $attributes['ranking_name'];

            $ranking = Ranking::create([
                'type' => RankingType::SHOW->value,
                'source_id' => $show->getKey(),
                'user_id' => $user->getKey(),
                'name' => Str::limit((new MaskProfanity)->handle($name, keep: $show->name), 30),
                'is_public' => $attributes['is_public'] ?? false,
                'comments_enabled' => $attributes['comments_enabled'] ?? false,
                'comments_replies_enabled' => $attributes['comments_replies_enabled'] ?? false,
            ]);

            $ranking->sortingState()->create();

            $songs = collect($attributes['tracks'])->map(fn ($track) => [
                'ranking_id' => $ranking->getKey(),
                'artist_id' => $artist->getKey(),
                'spotify_song_id' => $track['id'],
                'uuid' => $track['uuid'],
                'title' => $track['name'] ?? 'Episode removed from Spotify.',
                'cover' => $track['cover'] ?? 'https://i.imgur.com/MBDmIUg.png',
                'created_at' => now(),
                'updated_at' => now(),
            ])->toArray();

            Song::insert($songs);

            return $ranking;
        });
    }
}
