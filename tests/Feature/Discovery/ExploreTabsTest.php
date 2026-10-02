<?php

use App\Enums\ReviewType;
use App\Livewire\Explorer\ReviewsFeed;
use App\Livewire\Explorer\TierlistsFeed;
use App\Models\Review;
use App\Models\Tierlist;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe('explore tabs', function () {
    test('rankings tab is the default', function () {
        $user = kyle();
        publicCompletedRanking(attributes: ['user_id' => $user->getKey()]);

        actingAs($user)
            ->get(route('explore'))
            ->assertOk()
            ->assertSee("tab = 'rankings'", escape: false);
    });

    test('tier lists tab is visible when feature flag is active', function () {
        $user = kyle();

        actingAs($user)
            ->get(route('explore'))
            ->assertOk()
            ->assertSee('Tier Lists');
    });

    test('reviews tab is visible when feature flag is active', function () {
        actingAs(kyle())
            ->get(route('explore'))
            ->assertOk()
            ->assertSee("tab = 'reviews'", escape: false)
            ->assertSeeLivewire(ReviewsFeed::class);
    });
});

describe('tier lists feed', function () {
    test('public completed tier lists are visible', function () {
        $tierlist = publicCompletedTierlist();

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertSee($tierlist->name);
    });

    test('private tier lists are hidden', function () {
        $tierlist = Tierlist::factory()->create([
            'is_public' => false,
            'is_complete' => true,
            'completed_at' => now(),
            'name' => 'Secret List',
        ]);

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertDontSee('Secret List');
    });

    test('incomplete tier lists are hidden', function () {
        $tierlist = Tierlist::factory()->create([
            'is_public' => true,
            'is_complete' => false,
            'name' => 'Unfinished List',
        ]);

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertDontSee('Unfinished List');
    });

    test('can search tier lists by name', function () {
        $match = publicCompletedTierlist(['name' => 'Best Albums Ever']);
        $other = publicCompletedTierlist(['name' => 'Other Tier List']);

        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->set('search', 'Best Albums')
            ->call('performSearch')
            ->assertSee('Best Albums Ever')
            ->assertDontSee('Other Tier List');
    });

    test('load more increases visible results', function () {
        Livewire::actingAs(kyle())
            ->test(TierlistsFeed::class)
            ->assertSet('perPage', 12)
            ->call('loadMore')
            ->assertSet('perPage', 24);
    });
});

describe('reviews feed', function () {
    test('public published reviews are visible', function () {
        $review = publicPublishedReview();

        Livewire::actingAs(kyle())
            ->test(ReviewsFeed::class)
            ->assertSee($review->name);
    });

    test('private reviews and drafts are hidden', function () {
        Review::factory()->published()->createOne(['name' => 'Secret Review']);
        Review::factory()->public()->createOne(['name' => 'Unfinished Review']);

        Livewire::actingAs(kyle())
            ->test(ReviewsFeed::class)
            ->assertDontSee('Secret Review')
            ->assertDontSee('Unfinished Review');
    });

    test('can search reviews', function () {
        publicPublishedReview(['name' => 'Best Record Ever']);
        publicPublishedReview(['name' => 'Other Review', 'body_text' => 'nothing to see']);

        Livewire::actingAs(kyle())
            ->test(ReviewsFeed::class)
            ->set('search', 'Best Record')
            ->call('performSearch')
            ->assertSee('Best Record Ever')
            ->assertDontSee('Other Review');
    });

    test('filters by type, and a second click clears it', function () {
        publicPublishedReview(['name' => 'An Album Review']);
        Review::factory()->published()->public()->ofType(ReviewType::TRACK)->createOne(['name' => 'A Track Review']);

        Livewire::actingAs(kyle())
            ->test(ReviewsFeed::class)
            ->call('filterByType', 'track')
            ->assertSee('A Track Review')
            ->assertDontSee('An Album Review')
            ->call('filterByType', 'track')
            ->assertSet('type', null)
            ->assertSee('An Album Review');
    });

    test('resetting clears the search and the type', function () {
        Livewire::actingAs(kyle())
            ->test(ReviewsFeed::class)
            ->set('search', 'anything')
            ->call('filterByType', 'album')
            ->call('resetSearch')
            ->assertSet('search', null)
            ->assertSet('type', null);
    });

    test('load more increases visible results', function () {
        Livewire::actingAs(kyle())
            ->test(ReviewsFeed::class)
            ->assertSet('perPage', 12)
            ->call('loadMore')
            ->assertSet('perPage', 24);
    });
});
