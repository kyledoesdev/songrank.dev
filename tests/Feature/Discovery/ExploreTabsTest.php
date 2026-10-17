<?php

use App\Enums\ReviewType;
use App\Livewire\Explorer;
use App\Livewire\Explorer\RankingsFeed;
use App\Livewire\Explorer\ReviewsFeed;
use App\Livewire\Explorer\TierlistsFeed;
use App\Models\Review;
use App\Models\Tierlist;
use Livewire\Livewire;

describe('explore tabs', function () {
    test('rankings tab is the default', function () {
        Livewire::test(Explorer::class)
            ->assertSeeHtml("x-data=\"{ tab: 'rankings' }\"")
            ->assertSeeLivewire(RankingsFeed::class);
    });

    test('shows a tier lists tab', function () {
        Livewire::test(Explorer::class)
            ->assertSeeHtml("@click=\"tab = 'tierlists'\"")
            ->assertSeeLivewire(TierlistsFeed::class);
    });

    test('shows a reviews tab', function () {
        Livewire::test(Explorer::class)
            ->assertSeeHtml("@click=\"tab = 'reviews'\"")
            ->assertSeeLivewire(ReviewsFeed::class);
    });
});

describe('tier lists feed', function () {
    test('public completed tier lists are visible', function () {
        $tierlist = publicCompletedTierlist();

        expect(Livewire::test(TierlistsFeed::class)->instance()->tierlists->modelKeys())->toBe([$tierlist->getKey()]);
    });

    test('private tier lists are hidden', function () {
        $public = publicCompletedTierlist();
        Tierlist::factory()->complete()->create();

        expect(Livewire::test(TierlistsFeed::class)->instance()->tierlists->modelKeys())->toBe([$public->getKey()]);
    });

    test('incomplete tier lists are hidden', function () {
        $public = publicCompletedTierlist();
        Tierlist::factory()->public()->create();

        expect(Livewire::test(TierlistsFeed::class)->instance()->tierlists->modelKeys())->toBe([$public->getKey()]);
    });

    test('can search tier lists by name', function () {
        $match = publicCompletedTierlist(['name' => 'Best Albums Ever']);
        publicCompletedTierlist(['name' => 'Other Tier List']);

        $feed = Livewire::test(TierlistsFeed::class)
            ->set('search', 'Best Albums')
            ->call('performSearch');

        expect($feed->instance()->tierlists->modelKeys())->toBe([$match->getKey()]);
    });

    test('load more increases visible results', function () {
        Livewire::test(TierlistsFeed::class)
            ->assertSet('perPage', 12)
            ->call('loadMore')
            ->assertSet('perPage', 24);
    });
});

describe('reviews feed', function () {
    test('public published reviews are visible', function () {
        $review = publicPublishedReview();

        expect(Livewire::test(ReviewsFeed::class)->instance()->reviews->modelKeys())->toBe([$review->getKey()]);
    });

    test('private reviews and drafts are hidden', function () {
        $public = publicPublishedReview();
        Review::factory()->published()->createOne();
        Review::factory()->public()->createOne();

        expect(Livewire::test(ReviewsFeed::class)->instance()->reviews->modelKeys())->toBe([$public->getKey()]);
    });

    test('can search reviews', function () {
        $match = publicPublishedReview(['name' => 'Best Record Ever']);
        publicPublishedReview(['name' => 'Other Review', 'body_text' => 'nothing to see']);

        $feed = Livewire::test(ReviewsFeed::class)
            ->set('search', 'Best Record')
            ->call('performSearch');

        expect($feed->instance()->reviews->modelKeys())->toBe([$match->getKey()]);
    });

    test('filters by type, and a second click clears it', function () {
        $album = publicPublishedReview();
        $track = Review::factory()->published()->public()->ofType(ReviewType::TRACK)->createOne();

        $feed = Livewire::test(ReviewsFeed::class)->call('filterByType', 'track');

        expect($feed->instance()->reviews->modelKeys())->toBe([$track->getKey()]);

        $feed->call('filterByType', 'track')->assertSet('type', null);

        expect($feed->instance()->reviews->modelKeys())->toEqualCanonicalizing([$album->getKey(), $track->getKey()]);
    });

    test('resetting clears the search and the type', function () {
        Livewire::test(ReviewsFeed::class)
            ->set('search', 'anything')
            ->call('filterByType', 'album')
            ->call('resetSearch')
            ->assertSet('search', null)
            ->assertSet('type', null);
    });

    test('load more increases visible results', function () {
        Livewire::test(ReviewsFeed::class)
            ->assertSet('perPage', 12)
            ->call('loadMore')
            ->assertSet('perPage', 24);
    });
});
