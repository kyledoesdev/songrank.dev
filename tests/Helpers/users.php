<?php

use App\Enums\TierlistType;
use App\Livewire\Profile\Profile;
use App\Models\Crate;
use App\Models\ProLicense;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * The project's only admin persona: the admin panel is gated on is_dev, so
 * this is who you act as there. The dashboard widgets read dates in the
 * viewer's timezone, so the persona keeps to UTC and its windows hold still.
 */
function kyle(): User
{
    return User::factory()->createOne([
        'name' => 'Kyle',
        'is_dev' => true,
        'timezone' => 'UTC',
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
 * An account that has lost Pro. Whatever it made while it had Pro stays
 * exactly where it was.
 */
function revokePro(User $user): User
{
    $user->update(['is_pro' => false]);

    return $user;
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

/**
 * A user sitting on exactly $count reviews, for allowance assertions.
 */
function userWithReviews(int $count, array $attributes = []): User
{
    $user = User::factory()->createOne($attributes);

    Review::factory()->count($count)->for($user)->create();

    return $user;
}

/**
 * A user sitting on exactly $count top-level crates, for allowance assertions.
 */
function userWithCrates(int $count, array $attributes = []): User
{
    $user = User::factory()->createOne($attributes);

    Crate::factory()->count($count)->for($user)->create();

    return $user;
}

/**
 * $owner's profile page, as $viewer sees it.
 */
function profilePage(User $owner, User $viewer): Testable
{
    return Livewire::actingAs($viewer)->test(Profile::class, ['id' => $owner->spotify_id]);
}

/**
 * A user who has opted in to the newsletter and email blasts, or out of them.
 */
function newsletterSubscriber(bool $subscribed = true): User
{
    $user = User::factory()->createOne();

    $user->preferences->update(['recieve_newsletter_emails' => $subscribed]);

    return $user;
}
