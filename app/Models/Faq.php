<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Faq extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order',
        'question',
        'slug',
        'text',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function boot(): void
    {
        parent::boot();

        static::addGlobalScope('default_order', fn (Builder $query) => $query->orderBy('order', 'asc'));

        static::creating(function (Faq $faq): void {
            $faq->slug = Str::kebab($faq->question);
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
