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
}
