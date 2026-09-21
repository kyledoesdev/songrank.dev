<?php

use App\Models\Artist;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who can open a board', function () {
    it('is not there at all while the feature is off', function () {
        $tierlist = publicCompletedTierlist();

        actingAs(User::factory()->createOne());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertNotFound();
    });

    it('opens for its owner even before it is finished', function () {
        $owner = kyle();
        $tierlist = Tierlist::factory()->for($owner)->create();

        actingAs($owner);

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertOk();
    });

    it('opens for anyone once it is public and finished', function () {
        $tierlist = publicCompletedTierlist();

        actingAs(kyle());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertOk();
    });

    it('hides a private list from everybody else', function () {
        $tierlist = Tierlist::factory()->complete()->create();

        actingAs(kyle());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertNotFound();
    });

    it('hides an unfinished list from everybody else', function () {
        $tierlist = Tierlist::factory()->public()->create();

        actingAs(kyle());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertNotFound();
    });

    it('404s on a list that does not exist', function () {
        actingAs(kyle());

        get(route('tierlist', ['id' => 999]))->assertNotFound();
    });
});

describe('the board', function () {
    it('draws every tier, whether or not anything sits in it', function () {
        $tierlist = publicCompletedTierlist();

        actingAs(kyle());

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSeeInOrder(['S', 'A', 'B', 'C', 'D', 'F']);
    });

    it('draws a placed entry with its artwork and a way back to spotify', function () {
        $tierlist = publicCompletedTierlist();
        $artist = Artist::factory()->create([
            'artist_name' => 'Local Natives',
            'artist_img' => 'https://example.test/local-natives.png',
        ]);

        TierlistItem::factory()
            ->inTier($tierlist->placementTiers()->first())
            ->for($artist, 'entryable')
            ->create();

        actingAs(kyle());

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSee('Local Natives')
            ->assertSee('https://example.test/local-natives.png')
            ->assertSee($artist->spotifyUrl());
    });

    it('shows what is still waiting to be placed', function () {
        $tierlist = publicCompletedTierlist();

        TierlistItem::factory()->inTier($tierlist->bank)->create();

        actingAs(kyle());

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSee('Unranked')
            ->assertSee('1 still to place');
    });

    it('drops that section once everything has been placed', function () {
        $tierlist = publicCompletedTierlist();

        TierlistItem::factory()->inTier($tierlist->placementTiers()->first())->create();

        actingAs(kyle());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertDontSee('Unranked');
    });
});

describe('author attribution', function () {
    it('shows the tiered by banner for visitors', function () {
        $owner = kyle();
        $visitor = User::factory()->createOne(['is_dev' => true]);
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs($visitor)
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('Tiered by')
            ->assertSee($owner->name);
    });

    it('hides the tiered by banner for the owner', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertDontSee('Tiered by');
    });
});

describe('comments', function () {
    it('renders when comments are enabled', function () {
        $tierlist = publicCompletedTierlist(['comments_enabled' => true]);

        actingAs(kyle())
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('No comments yet');
    });

    it('is hidden when comments are disabled', function () {
        $tierlist = publicCompletedTierlist(['comments_enabled' => false]);

        actingAs(kyle())
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertDontSee('No comments yet');
    });
});
