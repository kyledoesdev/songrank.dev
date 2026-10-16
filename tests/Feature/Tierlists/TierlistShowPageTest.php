<?php

use App\Enums\ShareTarget;
use App\Livewire\Tierlist\TierlistShow;
use App\Models\Artist;
use App\Models\Tier;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;
use Illuminate\Support\Js;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who can open a board', function () {
    it('opens for guests once it is public and finished', function () {
        $tierlist = publicCompletedTierlist();

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertOk();
    });

    it('opens for its owner even before it is finished', function () {
        $owner = User::factory()->createOne();
        $tierlist = Tierlist::factory()->for($owner)->create();

        actingAs($owner);

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertOk();
    });

    it('opens for anyone once it is public and finished', function () {
        $tierlist = publicCompletedTierlist();

        actingAs(User::factory()->createOne());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertOk();
    });

    it('hides a private list from everybody else', function () {
        $tierlist = Tierlist::factory()->complete()->create();

        actingAs(User::factory()->createOne());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertNotFound();
    });

    it('hides an unfinished list from everybody else', function () {
        $tierlist = Tierlist::factory()->public()->create();

        actingAs(User::factory()->createOne());

        get(route('tierlist', ['id' => $tierlist->getKey()]))->assertNotFound();
    });

    it('404s on a list that does not exist', function () {
        actingAs(User::factory()->createOne());

        get(route('tierlist', ['id' => 999]))->assertNotFound();
    });
});

describe('the board', function () {
    it('draws every tier, whether or not anything sits in it', function () {
        $tierlist = publicCompletedTierlist();

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSeeHtmlInOrder($tierlist->placementTiers()->map(fn (Tier $tier) => 'wire:key="tier-'.$tier->getKey().'"')->all());
    });

    it('draws a placed entry with its artwork and a way back to spotify', function () {
        $tierlist = publicCompletedTierlist();
        $artist = Artist::factory()->create();

        TierlistItem::factory()
            ->inTier($tierlist->placementTiers()->first())
            ->for($artist, 'entryable')
            ->create();

        Livewire::test(TierlistShow::class, ['id' => $tierlist->getKey()])
            ->assertSee($artist->cover(), escape: false)
            ->assertSee($artist->spotifyUrl(), escape: false);
    });

    it('shows what is still waiting to be placed', function () {
        $tierlist = publicCompletedTierlist();

        TierlistItem::factory()->inTier($tierlist->bank)->create();

        Livewire::test(TierlistShow::class, ['id' => $tierlist->getKey()])
            ->assertSeeHtml('wire:key="tier-'.$tierlist->bank->getKey().'"');
    });

    it('drops that section once everything has been placed', function () {
        $tierlist = publicCompletedTierlist();

        Livewire::test(TierlistShow::class, ['id' => $tierlist->getKey()])
            ->assertDontSeeHtml('wire:key="tier-'.$tierlist->bank->getKey().'"');
    });
});

describe('author attribution', function () {
    it('shows the tiered by banner for visitors', function () {
        $owner = User::factory()->createOne();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        Livewire::actingAs(User::factory()->createOne())
            ->test(TierlistShow::class, ['id' => $tierlist->getKey()])
            ->assertSee(route('profile', ['id' => $owner->spotify_id]), escape: false);
    });

    it('hides the tiered by banner for the owner', function () {
        $owner = User::factory()->createOne();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        Livewire::actingAs($owner)
            ->test(TierlistShow::class, ['id' => $tierlist->getKey()])
            ->assertDontSee(route('profile', ['id' => $owner->spotify_id]), escape: false);
    });
});

describe('comments', function () {
    it('renders when comments are enabled', function () {
        $tierlist = publicCompletedTierlist(['comments_enabled' => true]);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSeeLivewire('comments');
    });

    it('is hidden when comments are disabled', function () {
        $tierlist = publicCompletedTierlist(['comments_enabled' => false]);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertDontSeeLivewire('comments');
    });
});

describe('sharing', function () {
    it('unfurls into the name, the top tier and its artwork', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
            ->assertSee('<meta property="og:title" content="Psych Rock">', escape: false)
            ->assertSee('<meta property="og:image" content="https://example.test/local-natives.png">', escape: false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false);

        expect($tierlist->shareDescription())->toContain('Local Natives');
    });

    it('leads with the highest tier that has anything in it', function () {
        $tierlist = publicCompletedTierlist();
        $pick = TierlistItem::factory()->inTier($tierlist->placementTiers()->get(1))->create();

        expect($tierlist->fresh()->topTierItems()->modelKeys())->toBe([$pick->getKey()]);
    });

    it('offers a link to post on each network, and the embed code', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);
        $url = route('tierlist', ['id' => $tierlist->getKey()]);

        $response = get($url);

        foreach (ShareTarget::cases() as $target) {
            $response->assertSee($target->intentUrl($tierlist->shareText(), $url));
        }

        $response->assertSee(Js::from($tierlist->embedCode())->toHtml(), escape: false);

        expect($tierlist->shareText())->toContain('Psych Rock');
    });

    it('describes the board to search engines as a list', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);

        get(route('tierlist', ['id' => $tierlist->getKey()]))
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
        $owner = User::factory()->createOne();
        $tierlist = Tierlist::factory()->for($owner)->complete()->create(['name' => 'Just Mine']);
        $url = route('tierlist', ['id' => $tierlist->getKey()]);

        $response = actingAs($owner)
            ->get($url)
            ->assertOk()
            ->assertDontSee('<meta property="og:title" content="Just Mine">', escape: false)
            ->assertDontSee(Js::from($tierlist->embedCode())->toHtml(), escape: false);

        foreach (ShareTarget::cases() as $target) {
            $response->assertDontSee($target->intentUrl($tierlist->shareText(), $url));
        }
    });
});

describe('the embed', function () {
    it('draws a card of a public, finished list that opens the list in the parent page', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);

        get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('src="https://example.test/local-natives.png"', escape: false)
            ->assertSeeHtmlInOrder(['<a', 'href="'.route('tierlist', ['id' => $tierlist->getKey()]).'"', 'target="_top"']);
    });

    it('carries the site head, credits the list as canonical and stays out of search itself', function () {
        $tierlist = tierlistWithTopPick(['name' => 'Psych Rock']);
        $listUrl = route('tierlist', ['id' => $tierlist->getKey()]);

        get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.$listUrl.'">', escape: false)
            ->assertSee('<meta name="robots" content="noindex, follow">', escape: false)
            ->assertSee('<meta property="og:title" content="Psych Rock">', escape: false)
            ->assertSee('apple-touch-icon', escape: false)
            ->assertSee('application/ld+json', escape: false)
            ->assertSee('"@type":"ItemList"', escape: false);
    });

    it('is never served for a private list, not even to its owner', function () {
        $owner = User::factory()->createOne();
        $tierlist = Tierlist::factory()->for($owner)->complete()->create();

        actingAs($owner)
            ->get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertNotFound();
    });

    it('is never served for an unfinished list', function () {
        $tierlist = Tierlist::factory()->public()->create();

        get(route('tierlist.embed', ['id' => $tierlist->getKey()]))->assertNotFound();
    });

    it('finds the top picks however far down the board they sit', function () {
        $tierlist = publicCompletedTierlist();

        get(route('tierlist.embed', ['id' => $tierlist->getKey()]))
            ->assertOk()
            ->assertSee('src="'.$tierlist->items->first()->entryable->cover().'"', escape: false);
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
