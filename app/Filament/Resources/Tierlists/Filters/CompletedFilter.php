<?php

namespace App\Filament\Resources\Tierlists\Filters;

use App\QueryBuilders\TierlistQueryBuilder;
use Filament\Tables\Filters\Filter;

class CompletedFilter extends Filter
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

        $this->query(fn (TierlistQueryBuilder $query): TierlistQueryBuilder => $query->completed());
    }
}
