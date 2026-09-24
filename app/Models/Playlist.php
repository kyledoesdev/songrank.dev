<?php

namespace App\Models;

use App\Contracts\SpotifyEntity;
use App\QueryBuilders\PlaylistQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[UseEloquentBuilder(PlaylistQueryBuilder::class)]
class Playlist extends Model implements SpotifyEntity
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'playlist_id',
        'creator_id',
        'creator_name',
        'name',
        'description',
        'cover',
        'track_count',
    ];

    public function casts(): array
    {
        return [
            'description' => 'string',
        ];
    }

    public function rankings(): MorphMany
    {
        return $this->morphMany(Ranking::class, 'source', 'type', 'source_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id', 'spotify_id')->withTrashed();
    }

    public function getDescriptionAttribute($value): ?string
    {
        return html_entity_decode($value);
    }

    public function cover(): ?string
    {
        return $this->cover;
    }

    public function name(): string
    {
        return $this->attributes['name'];
    }

    public function spotifyId(): string
    {
        return $this->playlist_id;
    }

    public function spotifyUrl(): string
    {
        return "https://open.spotify.com/playlist/{$this->playlist_id}";
    }
}
