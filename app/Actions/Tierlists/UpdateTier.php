<?php

namespace App\Actions\Tierlists;

use App\Models\Tier;

final class UpdateTier
{
    /**
     * @param  array{name: string, color: string}  $attributes
     */
    public function handle(Tier $tier, array $attributes): void
    {
        $tier->update([
            'name' => $attributes['name'],
            'color' => $attributes['color'],
        ]);
    }
}
