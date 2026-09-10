<?php

namespace App\Models\Concerns;

trait HasRankingAllowance
{
    public function rankingLimit(): ?int
    {
        return $this->is_pro
            ? config('billing.ranking_limits.pro')
            : config('billing.ranking_limits.free');
    }

    public function canCreateRanking(): bool
    {
        $limit = $this->rankingLimit();

        if (is_null($limit)) {
            return true;
        }

        return $this->rankings()->count() < $limit;
    }
}
