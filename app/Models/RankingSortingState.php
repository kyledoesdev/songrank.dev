<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class RankingSortingState extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ranking_id',
        'sorting_state',
        'aprox_comparisons',
        'completed_comparisons',
    ];

    protected function casts(): array
    {
        return [
            'sorting_state' => 'array',
        ];
    }

    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class);
    }

    /**
     * A sort that has made no comparisons yet, over the songs in the order given.
     *
     * @param  list<int>  $songIds
     * @return array{sorting_state: array<string, mixed>, aprox_comparisons: int, completed_comparisons: int}
     */
    public static function startingWith(array $songIds): array
    {
        $songsCount = count($songIds);

        return [
            'sorting_state' => [
                'stack' => [
                    [
                        'type' => 'sort',
                        'ids' => $songIds,
                        'start' => 0,
                        'end' => $songsCount - 1,
                        'depth' => 0,
                    ],
                ],
                'current_merge' => null,
                'completed_segments' => [],
                'decision_history' => [],
            ],
            'aprox_comparisons' => $songsCount > 1 ? intval($songsCount * log($songsCount, 2)) : 0,
            'completed_comparisons' => 0,
        ];
    }
}
