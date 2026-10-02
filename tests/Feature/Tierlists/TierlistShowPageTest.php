<?php

use App\Enums\ShareTarget;
use App\Models\Artist;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;
use Illuminate\Support\Js;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who can open a board', function () {
    it('opens for guests once it is public and finished', function () {
        $tierlist = publicCompletedTierlist();

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertOk();
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

describe('sharing', function () {
    it('unfurls into the name, the top tier and its artwork', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);

        actingAs(kyle())
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSee('<meta property="og:title" content="Psych Rock">', escape: false)
            ->assertSee('Top tier: Local Natives.', escape: false)
            ->assertSee('<meta property="og:image" content="https://example.test/local-natives.png">', escape: false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false);
    });

    it('leads with the highest tier that has anything in it', function () {
        $tierlist = publicCompletedTierlist();
        $names = $tierlist->items->map(fn (TierlistItem $item) => $item->entryable->name())->join(', ');

        actingAs(kyle())
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSee("Top tier: {$names}. A tier list by Artist from {$tierlist->user->name} on ".config('app.name'));
    });

    it('offers a link to post on each network, and the embed code', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);
        $url = route('tierlist', ['id' => $tierlist->getKey()]);

        $response = actingAs(kyle())->get($url);

        foreach (ShareTarget::cases() as $target) {
            $response->assertSee($target->intentUrl($tierlist->shareText(), $url));
        }

        $response->assertSee('Copy embed code')
            ->assertSee(Js::from($tierlist->embedCode())->toHtml(), escape: false);

        expect($tierlist->shareText())->toBe('Psych Rock — my tier list by Artist on '.config('app.name'));
    });

    it('describes the board to search engines as a list', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);

        actingAs(kyle())
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSee('"@type":"ItemList"', escape: false)
            ->assertSee('"name":"Local Natives"', escape: false)
            ->assertSee('<link rel="canonical" href="'.route('tierlist', ['id' => $tierlist->getKey()]).'">', escape: false);
    });

    it('puts a plain link back to the list under the embed', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);

        expect($tierlist->embedCode())
            ->toContain('<iframe src="'.route('tierlist.embed', ['id' => $tierlist->getKey()]).'"')
            ->toContain('<a href="'.route('tierlist', ['id' => $tierlist->getKey()]).'">Psych Rock</a>');
    });

    it('gives a private list no share tags and no share buttons', function () {
        $owner = kyle();
        $tierlist = Tierlist::factory()->for($owner)->complete()->create(['name' => 'Just Mine']);

        actingAs($owner)
            ->get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertDontSee('<meta property="og:title" content="Just Mine">', escape: false)
            ->assertDontSee('Copy embed code');
    });
});

describe('the embed', function () {
    it('draws a card of a public, finished list that opens the list in the parent page', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);

        actingAs(kyle())
            ->get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('Psych Rock')
            ->assertSee('https://example.test/local-natives.png')
            ->assertSee($tierlist->user->name)
            ->assertSee('target="_top"', escape: false)
            ->assertSee(route('tierlist', ['id' => $tierlist->getKey()]));
    });

    it('carries the site head, credits the list as canonical and stays out of search itself', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);
        $listUrl = route('tierlist', ['id' => $tierlist->getKey()]);

        actingAs(kyle())
            ->get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$listUrl.'">', escape: false)
            ->assertSee('<meta name="robots" content="noindex, follow">', escape: false)
            ->assertSee('<meta property="og:title" content="Psych Rock">', escape: false)
            ->assertSee('apple-touch-icon', escape: false)
            ->assertSee('application/ld+json', escape: false)
            ->assertSee('"@type":"ItemList"', escape: false);
    });

    it('is never served for a private list, not even to its owner', function () {
        $owner = kyle();
        $tierlist = Tierlist::factory()->for($owner)->complete()->create();

        actingAs($owner)
            ->get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertNotFound();
    });

    it('is never served for an unfinished list', function () {
        $tierlist = Tierlist::factory()->public()->create();

        actingAs(kyle())
            ->get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertNotFound();
    });

    it('finds the top picks however far down the board they sit', function () {
        $tierlist = publicCompletedTierlist();

        get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee($tierlist->items->first()->entryable->cover());
    });

    it('is served to guests, since whoever loads the host page is a guest here', function () {
        $tierlist = publicCompletedTierlist();

        get(route('tierlist.embed', ['id' => $tierlist->getKey()]))->assertOk();
    });
});

function tierlistWithTopPick(array $attributes = []): Tierlist
{
    $tierlist = publicCompletedTierlist($attributes);

    TierlistItem::factory()
        ->inTier($tierlist->placementTiers()->first())
        ->for(Artist::factory()->create([
            'artist_name' => 'Local Natives',
            'artist_img' => 'https://example.test/local-natives.png',
        ]), 'entryable')
        ->create();

    return $tierlist->fresh();
}
