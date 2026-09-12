<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TierlistItem extends Model
{
    protected $fillable = [
        'tierlist_id',
        'tier_id',
        'entryable_type',
        'entryable_id',
        'position',
    ];

    /* Relationships */

    public function tierlist(): BelongsTo
    {
        return $this->belongsTo(Tierlist::class);
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(Tier::class);
    }

    /**
     * The catalog record this entry points at — an Artist, Album or Track.
     * Every one of them implements SpotifyEntity, so one tile renders them all.
     */
    public function entryable(): MorphTo
    {
        return $this->morphTo();
    }
}
