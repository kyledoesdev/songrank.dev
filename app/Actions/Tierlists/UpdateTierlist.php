<?php

namespace App\Actions\Tierlists;

use App\Models\Tierlist;
use Illuminate\Support\Facades\DB;

final class UpdateTierlist
{
    public function handle(Tierlist $tierlist, array $data): void
    {
        DB::transaction(function () use ($tierlist, $data) {
            $tierlist->update([
                'name' => $data['name'],
                'is_public' => $data['is_public'],
                'comments_enabled' => $data['comments_enabled'],
                'comments_replies_enabled' => $data['comments_replies_enabled'],
            ]);
        });
    }
}
