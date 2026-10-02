<?php

namespace App\Filament\Filters;

use App\QueryBuilders\RankingQueryBuilder;
use App\QueryBuilders\ReviewQueryBuilder;
use App\QueryBuilders\TierlistQueryBuilder;
use Filament\Tables\Filters\Filter;

/**
 * Hides whatever its owner has not finished yet: a ranking still being sorted,
 * a tier list still being built, a review still in draft.
 */
class HideIncompleteFilter extends Filter
{
    public static function getDefaultName(): ?string
    {
        return 'hide_incomplete';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Hide Incomplete');

        $this->toggle();

        $this->default();

        $this->query(fn (RankingQueryBuilder|TierlistQueryBuilder|ReviewQueryBuilder $query) => $query->completed());
    }
}
