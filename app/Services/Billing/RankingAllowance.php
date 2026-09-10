<?php

namespace App\Services\Billing;

use App\Models\User;

/**
 * How many rankings a user has, and how many they are allowed.
 *
 * A `null` limit means unlimited, so Pro never leans on a sentinel like
 * PHP_INT_MAX. Every caller — the search guard, the setup header, the billing
 * page and the flash message — reads the same numbers from here.
 */
final class RankingAllowance
{
    public function __construct(private User $user) {}

    public function limit(): ?int
    {
        return $this->user->is_pro
            ? config('billing.ranking_limits.pro')
            : config('billing.ranking_limits.free');
    }

    public function used(): int
    {
        return once(fn (): int => $this->user->rankings()->count());
    }

    public function isUnlimited(): bool
    {
        return is_null($this->limit());
    }

    public function remaining(): ?int
    {
        if ($this->isUnlimited()) {
            return null;
        }

        return max(0, $this->limit() - $this->used());
    }

    public function exceeded(): bool
    {
        return $this->remaining() === 0;
    }

    /**
     * "84 of 100" — or just the count when there is no ceiling.
     */
    public function summary(): string
    {
        if ($this->isUnlimited()) {
            return trans_choice(':count ranking|:count rankings', $this->used(), ['count' => $this->used()]);
        }

        return "{$this->used()} of {$this->limit()}";
    }
}
