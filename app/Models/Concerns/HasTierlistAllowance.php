<?php

namespace App\Models\Concerns;

use App\Enums\TierlistType;

trait HasTierlistAllowance
{
    /**
     * How many lists of one type this account may own. `null` is unlimited.
     */
    public function tierlistLimit(TierlistType $type): ?int
    {
        $limits = $this->is_pro
            ? config('billing.tierlist_limits.pro')
            : config('billing.tierlist_limits.free');

        return $limits[$type->value] ?? null;
    }

    public function tierlistCount(TierlistType $type): int
    {
        return $this->tierlists()->where('type', $type->value)->count();
    }

    public function canCreateTierlist(TierlistType $type): bool
    {
        $limit = $this->tierlistLimit($type);

        if (is_null($limit)) {
            return true;
        }

        return $this->tierlistCount($type) < $limit;
    }

    /**
     * How many entries one of this account's lists may hold. Never null — a
     * board has to stay draggable.
     */
    public function tierlistItemLimit(): int
    {
        return $this->is_pro
            ? config('billing.tierlist_item_limits.pro')
            : config('billing.tierlist_item_limits.free');
    }
}
