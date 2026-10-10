<?php

namespace App\Models;

use App\QueryBuilders\CrateQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

#[UseEloquentBuilder(CrateQueryBuilder::class)]
class Crate extends Model
{
    use HasFactory;

    public const string DEFAULT_ICON = 'fa-folder';

    protected $fillable = [
        'user_id',
        'parent_id',
        'crate_icon_id',
        'name',
        'color',
        'depth',
        'position',
    ];

    protected $attributes = [
        'color' => '#A855F7',
        'depth' => 0,
    ];

    protected $with = ['icon'];

    protected function casts(): array
    {
        return [
            'depth' => 'integer',
            'position' => 'integer',
        ];
    }

    /**
     * A new crate joins the end of the list it lands in.
     */
    protected static function booted(): void
    {
        static::creating(function (Crate $crate) {
            $crate->position ??= Crate::query()->siblingsOf($crate)->count();
        });
    }

    /* Relationships */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Crate::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Crate::class, 'parent_id')->orderBy('position');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CrateItem::class)->orderBy('position');
    }

    public function icon(): BelongsTo
    {
        return $this->belongsTo(CrateIcon::class, 'crate_icon_id');
    }

    /* Helpers */

    public function iconClass(): string
    {
        return $this->icon?->icon ?? self::DEFAULT_ICON;
    }

    /**
     * This crate and every crate inside it, however deep.
     *
     * @return Collection<int, Crate>
     */
    public function branch(): Collection
    {
        return collect([$this])->merge($this->children->flatMap->branch());
    }

    public function isTopLevel(): bool
    {
        return is_null($this->parent_id);
    }

    /**
     * Every crate above this one, outermost first.
     *
     * @return Collection<int, Crate>
     */
    public function ancestors(): Collection
    {
        $ancestors = collect();
        $crate = $this->parent;

        while ($crate) {
            $ancestors->prepend($crate);
            $crate = $crate->parent;
        }

        return $ancestors;
    }

    /**
     * Crates are private, so only the owner sees one.
     */
    public function canBeSeen(): bool
    {
        return $this->user_id === Auth::id();
    }

    public function canBeEdited(): bool
    {
        return $this->user_id === Auth::id();
    }
}
