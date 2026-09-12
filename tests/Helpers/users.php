<?php

use App\Enums\TierlistType;
use App\Models\ProLicense;
use App\Models\Ranking;
use App\Models\Tierlist;
use App\Models\User;

/**
 * The project's only admin persona. The panel and every feature flag are
 * gated on is_dev, so this is who you act as to reach either.
 */
function kyle(): User
{
    return User::factory()->createOne([
        'name' => 'Kyle',
        'is_dev' => true,
    ]);
}

/**
 * A user holding an active, paid Pro license.
 */
function proUser(array $attributes = []): User
{
    $user = User::factory()->createOne($attributes);

    ProLicense::factory()->active()->for($user)->create();

    /* ProLicenseObserver has since flipped is_pro on the row, not on this instance. */
    return $user->fresh();
}

/**
 * A user sitting on exactly $count rankings, for allowance assertions.
 */
function userWithRankings(int $count, array $attributes = []): User
{
    $user = User::factory()->createOne($attributes);

    Ranking::factory()->count($count)->for($user)->create();

    return $user;
}

/**
 * A user sitting on exactly $count tier lists of one type, for allowance assertions.
 */
function userWithTierlists(int $count, TierlistType $type = TierlistType::ARTIST, array $attributes = []): User
{
    $user = User::factory()->createOne($attributes);

    Tierlist::factory()
        ->count($count)
        ->for($user)
        ->create(['type' => $type->value]);

    return $user;
}
