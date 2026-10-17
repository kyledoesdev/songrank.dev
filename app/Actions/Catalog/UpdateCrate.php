<?php

namespace App\Actions\Catalog;

use App\Actions\MaskProfanity;
use App\Models\Crate;
use App\Models\CrateIcon;
use Illuminate\Support\Str;

final class UpdateCrate
{
    /**
     * Refuses an icon the owner may not pick: an inactive one, or a Pro one on
     * an account without Pro. The icon a crate already has, it keeps.
     *
     * @param  array{name: string, color: string, crate_icon_id: ?int}  $attributes
     */
    public function handle(Crate $crate, array $attributes): bool
    {
        if (! $this->mayUseIcon($crate, $attributes['crate_icon_id'])) {
            return false;
        }

        $crate->update([
            'name' => Str::limit((new MaskProfanity)->handle($attributes['name']), 40, ''),
            'color' => $attributes['color'],
            'crate_icon_id' => $attributes['crate_icon_id'],
        ]);

        return true;
    }

    private function mayUseIcon(Crate $crate, ?int $iconId): bool
    {
        if (is_null($iconId) || $iconId === $crate->crate_icon_id) {
            return true;
        }

        return CrateIcon::query()
            ->active()
            ->availableTo($crate->user)
            ->whereKey($iconId)
            ->exists();
    }
}
