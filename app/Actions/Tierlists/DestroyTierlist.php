<?php

namespace App\Actions\Tierlists;

use App\Models\Tierlist;
use Illuminate\Support\Facades\DB;

final class DestroyTierlist
{
    public function handle(Tierlist $tierlist): void
    {
        DB::transaction(fn () => $tierlist->delete());
    }
}
