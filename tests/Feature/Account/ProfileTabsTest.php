<?php

use App\Livewire\Tierlist\Card as TierlistCard;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe('profile tabs', function () {
    test('both tabs are visible when user has rankings and tier lists', function () {
        $owner = User::factory()->createOne();

        publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);
        publicCompletedTierlist(['user_id' => $owner->getKey()]);

        profilePage($owner, $owner)
            ->assertViewHas('showTabs', true)
            ->assertSeeHtml("@click=\"tab = 'rankings'\"")
            ->assertSeeHtml("@click=\"tab = 'tierlists'\"");
    });

    test('no tab bar when user has only rankings', function () {
        $owner = User::factory()->createOne();

        $ranking = publicCompletedRanking(attributes: ['user_id' => $owner->getKey()]);

        $page = profilePage($owner, $owner)->assertViewHas('showTabs', false);

        expect($page->instance()->rankings->modelKeys())->toBe([$ranking->getKey()]);
    });

    test('no tab bar when user has only tier lists', function () {
        $owner = User::factory()->createOne();

        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        $page = profilePage($owner, $owner)->assertViewHas('showTabs', false);

        expect($page->instance()->tierlists->modelKeys())->toBe([$tierlist->getKey()]);
    });

    test('empty state when user has no content', function () {
        $owner = User::factory()->createOne();

        profilePage($owner, $owner)
            ->assertViewHas('hasRankings', false)
            ->assertViewHas('hasTierlists', false)
            ->assertViewHas('hasReviews', false);
    });

    test('empty state shows dashboard link for the profile owner', function () {
        $owner = User::factory()->createOne();

        profilePage($owner, $owner)->assertSee(route('dashboard'), escape: false);
    });

    test('empty state hides dashboard link for visitors', function () {
        $owner = User::factory()->createOne();

        profilePage($owner, User::factory()->createOne())->assertDontSee(route('dashboard'), escape: false);
    });

    test('visitors see only public completed tier lists', function () {
        $owner = User::factory()->createOne();

        $publicComplete = publicCompletedTierlist(['user_id' => $owner->getKey()]);
        Tierlist::factory()->for($owner)->complete()->create();
        Tierlist::factory()->for($owner)->public()->create();

        $tierlists = profilePage($owner, User::factory()->createOne())->instance()->tierlists;

        expect($tierlists->modelKeys())->toBe([$publicComplete->getKey()]);
    });

    test('owner sees their own in-progress and private tier lists', function () {
        $owner = User::factory()->createOne();

        $complete = publicCompletedTierlist(['user_id' => $owner->getKey()]);
        $inProgress = Tierlist::factory()->for($owner)->public()->create();
        $private = Tierlist::factory()->for($owner)->complete()->create();

        expect(profilePage($owner, $owner)->instance()->tierlists->modelKeys())
            ->toEqualCanonicalizing([$complete->getKey(), $inProgress->getKey(), $private->getKey()]);
    });
});

describe('the reviews tab', function () {
    test('sits beside the other tabs without an empty rankings tab', function () {
        $owner = User::factory()->createOne();

        publicCompletedTierlist(['user_id' => $owner->getKey()]);
        publicPublishedReview(['user_id' => $owner->getKey()]);

        profilePage($owner, $owner)
            ->assertSeeHtml("@click=\"tab = 'reviews'\"")
            ->assertSeeHtml("@click=\"tab = 'tierlists'\"")
            ->assertDontSeeHtml("@click=\"tab = 'rankings'\"");
    });

    test('visitors see only public published reviews', function () {
        $owner = User::factory()->createOne();

        $public = publicPublishedReview(['user_id' => $owner->getKey()]);
        Review::factory()->for($owner)->published()->createOne();
        Review::factory()->for($owner)->public()->createOne();

        $reviews = profilePage($owner, User::factory()->createOne())->instance()->reviews;

        expect($reviews->modelKeys())->toBe([$public->getKey()]);
    });

    test('owner sees their own drafts and private reviews', function () {
        $owner = User::factory()->createOne();

        $private = Review::factory()->for($owner)->published()->createOne();
        $draft = Review::factory()->for($owner)->createOne();

        expect(profilePage($owner, $owner)->instance()->reviews->modelKeys())
            ->toEqualCanonicalizing([$private->getKey(), $draft->getKey()]);
    });

    test('owner sees the edit and delete buttons on their review cards', function () {
        $owner = User::factory()->createOne();
        $review = publicPublishedReview(['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertSee(route('review.edit', ['id' => $review->getKey()]), escape: false)
            ->assertSee("action: 'destroy'", escape: false);
    });

    test('visitors do not see the edit and delete buttons', function () {
        $owner = User::factory()->createOne();
        $review = publicPublishedReview(['user_id' => $owner->getKey()]);

        actingAs(User::factory()->createOne())
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertDontSee(route('review.edit', ['id' => $review->getKey()]), escape: false)
            ->assertDontSee("action: 'destroy'", escape: false);
    });
});

describe('tier list card actions', function () {
    test('owner sees edit and delete buttons on their tier list cards', function () {
        $owner = User::factory()->createOne();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertSee(route('tierlist.edit', ['id' => $tierlist->getKey()]), escape: false)
            ->assertSee("action: 'destroy'", escape: false);
    });

    test('visitors do not see edit and delete buttons', function () {
        $owner = User::factory()->createOne();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs(User::factory()->createOne())
            ->get(route('profile', ['id' => $owner->spotify_id]))
            ->assertOk()
            ->assertDontSee(route('tierlist.edit', ['id' => $tierlist->getKey()]), escape: false)
            ->assertDontSee("action: 'destroy'", escape: false);
    });

    test('owner can delete a tier list from the profile card', function () {
        $owner = User::factory()->createOne();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        Livewire::actingAs($owner)
            ->test(TierlistCard::class, ['tierlist' => $tierlist])
            ->call('destroy')
            ->assertDispatched('tierlists-updated');

        expect($tierlist->fresh()->trashed())->toBeTrue();
    });

    test('non-owner cannot delete a tier list from the card', function () {
        $owner = User::factory()->createOne();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(TierlistCard::class, ['tierlist' => $tierlist])
            ->call('destroy')
            ->assertForbidden();

        expect($tierlist->fresh()->trashed())->toBeFalse();
    });
});
