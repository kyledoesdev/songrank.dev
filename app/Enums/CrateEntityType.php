<?php

namespace App\Enums;

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use Illuminate\Database\Eloquent\Builder;

enum CrateEntityType: string
{
    case RANKING = 'ranking';
    case TIERLIST = 'tierlist';
    case REVIEW = 'review';

    public function label(): string
    {
        return match ($this) {
            self::RANKING => 'Rankings',
            self::TIERLIST => 'Tier lists',
            self::REVIEW => 'Reviews',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::RANKING => 'fa-ranking-star',
            self::TIERLIST => 'fa-layer-group',
            self::REVIEW => 'fa-pen-nib',
        };
    }

    /**
     * @return class-string<Ranking|Tierlist|Review>
     */
    public function model(): string
    {
        return match ($this) {
            self::RANKING => Ranking::class,
            self::TIERLIST => Tierlist::class,
            self::REVIEW => Review::class,
        };
    }

    public function query(): Builder
    {
        return $this->model()::query();
    }
}
