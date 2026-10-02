<?php

namespace App\Models;

use App\Enums\Billing\Plan;
use App\Observers\PlanFeatureObserver;
use App\QueryBuilders\PlanFeatureQueryBuilder;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[UseEloquentBuilder(PlanFeatureQueryBuilder::class)]
#[ObservedBy(PlanFeatureObserver::class)]
class PlanFeature extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const string CACHE_KEY = 'plan-features';

    protected $fillable = [
        'plan',
        'label',
        'detail',
        'is_included',
        'is_coming_soon',
        'order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'plan' => Plan::class,
            'is_included' => 'boolean',
            'is_coming_soon' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PlanFeature $feature) {
            $feature->order ??= (int) self::query()->where('plan', $feature->plan)->max('order') + 1;
        });
    }

    /**
     * Each plan's features in the order its pricing card lists them.
     *
     * @return Collection<string, Collection<int, PlanFeature>>
     */
    public static function cached(): Collection
    {
        return cache()->remember(
            self::CACHE_KEY,
            now()->addDay(),
            fn () => self::query()->forPricingCards()->get()->groupBy(fn (PlanFeature $feature) => $feature->plan->value)
        );
    }

    public function renderedLabel(): string
    {
        return $this->plan->fillPlaceholders($this->label);
    }

    public function renderedDetail(): ?string
    {
        return $this->detail ? $this->plan->fillPlaceholders($this->detail) : null;
    }
}
