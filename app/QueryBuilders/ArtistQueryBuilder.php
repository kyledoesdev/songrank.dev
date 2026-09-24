<?php

namespace App\QueryBuilders;

use App\Enums\RankingType;
use Illuminate\Database\Eloquent\Builder;

class ArtistQueryBuilder extends Builder
{
    /**
     * The name of an artist we hold a picture for, for search placeholders.
     */
    public function randomName(): ?string
    {
        return $this->newQuery()
            ->whereNotNull('artist_img')
            ->inRandomOrder()
            ->first()?->artist_name;
    }

    public function topArtists(int $limit = 10): static
    {
        return $this->newQuery()
            ->selectRaw('
                count(rankings.source_id) as artist_rankings_count,
                artists.id,
                artists.artist_id,
                artists.artist_name,
                artists.artist_img
            ')
            ->join('rankings', function ($join) {
                $join->on('rankings.source_id', '=', 'artists.id')
                    ->where('rankings.type', RankingType::ARTIST->value)
                    ->whereNull('rankings.deleted_at')
                    ->where('rankings.is_ranked', true)
                    ->where('rankings.is_public', true);
            })
            ->whereNotNull('artists.artist_img')
            ->groupBy('rankings.source_id')
            ->orderBy('artist_rankings_count', 'desc')
            ->orderBy('artists.artist_name', 'asc')
            ->limit($limit);
    }
}
