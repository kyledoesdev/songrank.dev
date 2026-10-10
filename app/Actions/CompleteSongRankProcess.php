<?php

namespace App\Actions;

use App\Models\Ranking;
use App\Models\Song;
use App\Stats\RankingCompletedStat;
use Illuminate\Support\Facades\DB;

final class CompleteSongRankProcess
{
    public function handle(Ranking $ranking, array $attributes)
    {
        DB::transaction(function () use ($ranking, $attributes) {
            /* update that the ranking was completed */
            $ranking->update([
                'is_ranked' => true,
                'completed_at' => now(),
            ]);

            /* purge the state to clear data */
            $ranking->sortingState->update([
                'sorting_state' => null,
            ]);

            /* map the songs and upsert them */
            $data = collect($attributes['finalSongIds'])->map(function ($id, $index) use ($ranking) {
                $song = $ranking->songs->firstWhere('id', $id);

                return [
                    'id' => $song->getKey(),
                    'ranking_id' => $ranking->getKey(),
                    'spotify_song_id' => $song->spotify_song_id,
                    'uuid' => $song->uuid,
                    'title' => $song->title,
                    'cover' => $song->cover,
                    'rank' => $index + 1,
                    'created_at' => $song->created_at,
                    'updated_at' => now(),
                ];
            })->toArray();

            Song::upsert($data, ['id'], ['rank', 'updated_at']);

            RankingCompletedStat::increase();
        });
    }
}
