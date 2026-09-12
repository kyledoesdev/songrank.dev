<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tier extends Model
{
    protected $fillable = [
        'tierlist_id',
        'name',
        'slug',
        'color',
        'position',
        'is_bank',
    ];

    protected function casts(): array
    {
        return [
            'is_bank' => 'boolean',
        ];
    }

    /* Relationships */

    public function tierlist(): BelongsTo
    {
        return $this->belongsTo(Tierlist::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TierlistItem::class)->orderBy('position');
    }
}
