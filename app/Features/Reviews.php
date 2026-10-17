<?php

namespace App\Features;

use App\Models\User;
use Laravel\Pennant\Attributes\Name;

#[Name('reviews')]
class Reviews
{
    public function resolve(?User $user): bool
    {
        return ! is_null($user);
    }
}
