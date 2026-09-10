<?php

namespace App\Features;

use App\Models\User;
use Laravel\Pennant\Attributes\Name;

#[Name('songrank-pro')]
class SongRankPro
{
    public function resolve(?User $user): bool
    {
        if (is_null($user)) {
            return false;
        }

        return $user->is_dev;
    }
}
