<?php

namespace App\Models;

use App\QueryBuilders\SongQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseEloquentBuilder(SongQueryBuilder::class)]
class Song extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'ranking_id',
        'spotify_song_id',
        'uuid',
        'title',
        'rank',
        'cover',
    ];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('default_order', fn (Builder $query) => $query->orderBy('rank', 'asc'));
    }

    protected function casts(): array
    {
        return [
            'featured_artist' => 'boolean',
        ];
    }

    public function ranking(): BelongsTo
    {
        return $this->belongsTo(Ranking::class);
    }

    public function artist(): HasOne
    {
        return $this->hasOne(Artist::class, 'id', 'artist_id');
    }
}
