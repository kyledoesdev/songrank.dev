<?php

namespace App\QueryBuilders;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CrateIconQueryBuilder extends Builder
{
    public function active(): static
    {
        return $this->where('is_active', true);
    }

    public function availableTo(User $user): static
    {
        return $this->when(! $user->is_pro, fn (Builder $query) => $query->where('is_pro', false));
    }

    public function ordered(): static
    {
        return $this->orderBy('position')->orderBy('id');
    }
}
