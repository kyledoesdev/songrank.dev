<?php

namespace App\Actions\Catalog;

use App\Models\CrateItem;
use Illuminate\Support\Facades\DB;

final class RemoveCrateItem
{
    public function handle(CrateItem $item): void
    {
        DB::transaction(function () use ($item) {
            $item->delete();

            CrateItem::query()
                ->where('crate_id', $item->crate_id)
                ->closeGapAt($item->position);
        });
    }
}
