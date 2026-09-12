<?php

namespace App\Models;

use App\Contracts\SpotifyEntity;
use App\QueryBuilders\ArtistQueryBuilder;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseEloquentBuilder(ArtistQueryBuilder::class)]
class Artist extends Model implements SpotifyEntity
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'artist_id',
        'artist_name',
        'artist_img',
        'is_podcast',
    ];

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope('default_order', fn (Builder $query) => $query->orderBy('artist_name', 'asc'));
    }

    public function casts(): array
    {
        return [
            'is_podcast' => 'boolean',
        ];
    }

    public function rankings(): MorphMany
    {
        return $this->morphMany(Ranking::class, 'source', 'type', 'source_id');
    }

    public function cover(): ?string
    {
        return $this->artist_img;
    }

    public function name(): string
    {
        return $this->artist_name;
    }

    public function spotifyId(): string
    {
        return $this->artist_id;
    }

    public function spotifyUrl(): string
    {
        return "https://open.spotify.com/artist/{$this->artist_id}";
    }
}
