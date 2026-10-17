<?php

use App\Models\Crate;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * The names in one of a user's crate lists, in the order the catalog shows
 * them.
 *
 * @return list<string>
 */
function crateNamesShown(User $user, ?Crate $parent = null): array
{
    return Crate::query()
        ->ownedBy($user)
        ->inside($parent)
        ->ordered($user->fresh())
        ->pluck('name')
        ->all();
}

/**
 * A crate's items as the entities filed in it, in stored order, each as
 * "Class:id" so a mix of types still compares in one assertion.
 *
 * @return list<string>
 */
function crateContents(Crate $crate): array
{
    return $crate->items()
        ->get()
        ->map(fn ($item) => class_basename($item->entity_type).':'.$item->entity_id)
        ->all();
}

/**
 * How crateContents() names one entity.
 */
function crateEntry(Model $entity): string
{
    return class_basename($entity).':'.$entity->getKey();
}
