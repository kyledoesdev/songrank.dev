<?php

namespace App\Actions\Catalog;

use App\Models\Crate;
use Illuminate\Support\Facades\DB;

final class DestroyCrate
{
    /**
     * The crates inside it and everything filed in any of them go too, but the
     * rankings, tier lists and reviews themselves are never touched.
     */
    public function handle(Crate $crate): void
    {
        DB::transaction(function () use ($crate) {
            $crate->delete();

            Crate::query()->siblingsOf($crate)->closeGapAt($crate->position);
        });
    }
}
