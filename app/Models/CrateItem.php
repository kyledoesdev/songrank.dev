<?php

namespace App\Models;

use App\QueryBuilders\CrateItemQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[UseEloquentBuilder(CrateItemQueryBuilder::class)]
class CrateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'crate_id',
        'entity_type',
        'entity_id',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    /**
     * A new item joins the end of its crate.
     */
    protected static function booted(): void
    {
        static::creating(function (CrateItem $item) {
            $item->position ??= CrateItem::query()->where('crate_id', $item->crate_id)->count();
        });
    }

    /* Relationships */

    public function crate(): BelongsTo
    {
        return $this->belongsTo(Crate::class);
    }

    /**
     * What is filed here: a Ranking, a Tierlist or a Review.
     */
    public function entity(): MorphTo
    {
        return $this->morphTo();
    }
}
