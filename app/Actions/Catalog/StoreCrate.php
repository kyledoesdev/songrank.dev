<?php

namespace App\Actions\Catalog;

use App\Actions\MaskProfanity;
use App\Models\Crate;
use App\Models\User;
use Illuminate\Support\Str;

final class StoreCrate
{
    /**
     * Adds a crate to the end of its list. Refuses, returning null, once the
     * account is out of crates or when $parent is somewhere it may not nest.
     *
     * @param  array{name: string}  $attributes
     */
    public function handle(User $user, array $attributes, ?Crate $parent = null): ?Crate
    {
        if (! $user->canCreateCrate()) {
            return null;
        }

        if ($parent && ! $user->canNestIn($parent)) {
            return null;
        }

        return $user->crates()->create([
            'parent_id' => $parent?->getKey(),
            'name' => Str::limit((new MaskProfanity)->handle($attributes['name']), 40, ''),
            'depth' => $parent ? $parent->depth + 1 : 0,
        ]);
    }
}
