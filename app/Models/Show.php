<?php

namespace App\Models;

use App\Contracts\SpotifyEntity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Show extends Model implements SpotifyEntity
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'show_id',
        'publisher',
        'name',
        'description',
        'cover',
        'episode_count',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'description' => 'string',
            'data' => 'array',
        ];
    }

    public function rankings(): MorphMany
    {
        return $this->morphMany(Ranking::class, 'source', 'type', 'source_id');
    }

    public function getDescriptionAttribute($value): ?string
    {
        return html_entity_decode($value);
    }

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
        return $this->show_id;
    }

    public function spotifyUrl(): string
    {
        return "https://open.spotify.com/show/{$this->show_id}";
    }
}
