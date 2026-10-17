<?php

namespace App\Actions\Rankings;

use App\Models\Ranking;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class DestroyRanking
{
    public function handle(User $user, Ranking $ranking)
    {
        DB::transaction(function () use ($ranking) {
            $ranking->songs()->delete();
            $ranking->delete();
        });
    }
}
