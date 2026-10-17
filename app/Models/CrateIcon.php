<?php

namespace App\Models;

use App\QueryBuilders\CrateIconQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[UseEloquentBuilder(CrateIconQueryBuilder::class)]
class CrateIcon extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'icon',
        'is_pro',
        'is_active',
        'position',
    ];

    protected $attributes = [
        'is_pro' => false,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_pro' => 'boolean',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (CrateIcon $icon) {
            $icon->position ??= (int) self::query()->max('position') + 1;
        });
    }

    public function crates(): HasMany
    {
        return $this->hasMany(Crate::class);
    }
}
