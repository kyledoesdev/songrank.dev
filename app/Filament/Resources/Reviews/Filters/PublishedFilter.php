<?php

namespace App\Filament\Resources\Reviews\Filters;

use App\QueryBuilders\ReviewQueryBuilder;
use Filament\Tables\Filters\Filter;

class PublishedFilter extends Filter
{
    public static function getDefaultName(): ?string
    {
        return 'hide_drafts';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Hide Drafts');

        $this->toggle();

        $this->default();

        $this->query(fn (ReviewQueryBuilder $query): ReviewQueryBuilder => $query->published());
    }
}
