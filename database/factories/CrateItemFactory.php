<?php

namespace Database\Factories;

use App\Models\Crate;
use App\Models\CrateItem;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;

/**
 * @extends Factory<CrateItem>
 */
class CrateItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'crate_id' => Crate::factory(),
            'entity_type' => (new Ranking)->getMorphClass(),
            'entity_id' => fn (array $attributes) => $this->ownedEntity(Ranking::class, $attributes),
        ];
    }

    public function forRanking(?Ranking $ranking = null): static
    {
        return $this->forEntity(Ranking::class, $ranking);
    }

    public function forTierlist(?Tierlist $tierlist = null): static
    {
        return $this->forEntity(Tierlist::class, $tierlist);
    }

    public function forReview(?Review $review = null): static
    {
        return $this->forEntity(Review::class, $review);
    }

    /**
     * Without an entity to file, makes one belonging to the crate's owner, as
     * only an owner's own records can be filed in their crates.
     *
     * @param  class-string<Ranking|Tierlist|Review>  $model
     */
    private function forEntity(string $model, ?Model $entity): static
    {
        return $this->state([
            'entity_type' => (new $model)->getMorphClass(),
            'entity_id' => fn (array $attributes) => $entity?->getKey() ?? $this->ownedEntity($model, $attributes),
        ]);
    }

    /**
     * @param  class-string<Ranking|Tierlist|Review>  $model
     */
    private function ownedEntity(string $model, array $attributes): int
    {
        return $model::factory()->createOne([
            'user_id' => Crate::query()->findOrFail($attributes['crate_id'])->user_id,
        ])->getKey();
    }
}
