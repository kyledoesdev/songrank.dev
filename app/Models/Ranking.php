<?php

namespace App\Models;

use App\Enums\RankingType;
use App\QueryBuilders\RankingQueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Kyledoesdev\Essentials\Concerns\HasStatsAfterEvents;
use Spatie\Comments\Models\Concerns\HasComments;

#[UseEloquentBuilder(RankingQueryBuilder::class)]
class Ranking extends Model
{
    use HasComments;
    use HasFactory;
    use HasStatsAfterEvents;
    use SoftDeletes;

    public const MAX_SONGS = 500;

    protected $fillable = [
        'user_id',
        'type',
        'source_id',
        'name',
        'is_ranked',
        'is_public',
        'comments_enabled',
        'comments_replies_enabled',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => RankingType::class,
            'is_ranked' => 'boolean',
            'is_public' => 'boolean',
            'has_podcast_episode' => 'boolean',
            'comments_enabled' => 'boolean',
            'comments_replies_enabled' => 'boolean',
        ];
    }

    /* Relationships */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo('source', 'type', 'source_id');
    }

    public function songs(): HasMany
    {
        return $this->hasMany(Song::class);
    }

    public function sortingState(): HasOne
    {
        return $this->hasOne(RankingSortingState::class, 'ranking_id', 'id');
    }

    /* Attributes */

    public function getCompletedAtAttribute(): string
    {
        if (is_null($this->attributes['completed_at'])) {
            return 'In Progress';
        }

        return Carbon::parse($this->attributes['completed_at'])->diffForHumans();
    }

    public function getFormattedCompletedAtAttribute(): string
    {
        if (is_null($this->attributes['completed_at'])) {
            return 'In Progress';
        }

        return Carbon::parse($this->attributes['completed_at'])->inUserTimezone()->format('M d, Y g:i A T');
    }

    public function isPlaylistType(): bool
    {
        return $this->type === RankingType::PLAYLIST;
    }

    public function isShowType(): bool
    {
        return $this->type === RankingType::SHOW;
    }

    /* Helpers */

    public function canBeSeen(): bool
    {
        if ($this->user_id == Auth::id()) {
            return true;
        }

        return $this->is_public && $this->is_ranked;
    }

    /* contracts */

    public function commentableName(): string
    {
        return $this->name;
    }

    public function commentUrl(): string
    {
        return route('ranking', ['id' => $this->getKey()]);
    }
}
