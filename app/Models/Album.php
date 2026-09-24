<?php

namespace App\Models;

use App\Contracts\SpotifyEntity;
use App\QueryBuilders\AlbumQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseEloquentBuilder(AlbumQueryBuilder::class)]
class Album extends Model implements SpotifyEntity
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'album_id',
        'artist_id',
        'name',
        'cover',
        'release_date',
        'total_tracks',
    ];

    /* Relationships */

    /**
     * The primary credited artist. A compilation keeps only the first, the same
     * way `songs.artist_id` does.
     */
    public function artist(): BelongsTo
    {
        return $this->belongsTo(Artist::class);
    }

    public function tierlistItems(): MorphMany
    {
        return $this->morphMany(TierlistItem::class, 'entryable');
    }

    /* Contracts */

    public function cover(): ?string
    {
        return $this->attributes['cover'] ?? null;
    }

    public function name(): string
    {
        return $this->attributes['name'];
    }

    public function spotifyId(): string
    {
        return $this->album_id;
    }

    public function spotifyUrl(): string
    {
        return "https://open.spotify.com/album/{$this->album_id}";
    }
}
