<?php

namespace App\Models;

use App\Contracts\SpotifyEntity;
use App\QueryBuilders\TrackQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A track in the shared Spotify catalog, deduplicated by spotify id.
 *
 * Not to be confused with `Song`, which is a single entry inside one ranking
 * and carries a `ranking_id` and a `rank`.
 */
#[UseEloquentBuilder(TrackQueryBuilder::class)]
class Track extends Model implements SpotifyEntity
{
    protected $fillable = [
        'track_id',
        'artist_id',
        'name',
        'cover',
        'album_name',
    ];

    /* Relationships */

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
        return $this->track_id;
    }

    public function spotifyUrl(): string
    {
        return "https://open.spotify.com/track/{$this->track_id}";
    }
}
