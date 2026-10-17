<?php

use App\Actions\Rankings\DestroyRanking;
use App\Actions\Reviews\PublishReview;
use App\Actions\Reviews\UpdateReview;
use App\Actions\Tierlists\CompleteTierlist;
use App\Actions\Tierlists\UpdateTierlist;
use App\Livewire\Explorer\RankingsFeed;
use App\Livewire\Explorer\ReviewsFeed;
use App\Livewire\Explorer\TierlistsFeed;
use App\Models\Review;
use App\Models\Tierlist;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

describe('the rankings count', function () {
    it('drops a ranking that is made private', function () {
        $ranking = publicCompletedRanking();

        expect(countShownBy(RankingsFeed::class))->toBe(1);

        $ranking->update(['is_public' => false]);

        expect(countShownBy(RankingsFeed::class))->toBe(0);
    });

    it('drops a deleted ranking', function () {
        $ranking = publicCompletedRanking();

        expect(countShownBy(RankingsFeed::class))->toBe(1);

        (new DestroyRanking)->handle($ranking->user, $ranking);

        expect(countShownBy(RankingsFeed::class))->toBe(0);
    });
});

describe('the tier lists count', function () {
    it('counts a tier list once it is finished', function () {
        $tierlist = Tierlist::factory()->public()->create();

        expect(countShownBy(TierlistsFeed::class))->toBe(0);

        (new CompleteTierlist)->handle($tierlist);

        expect(countShownBy(TierlistsFeed::class))->toBe(1);
    });

    it('drops a tier list that is made private', function () {
        $tierlist = publicCompletedTierlist();

        expect(countShownBy(TierlistsFeed::class))->toBe(1);

        (new UpdateTierlist)->handle($tierlist, [
            'name' => $tierlist->name,
            'is_public' => false,
            'comments_enabled' => false,
            'comments_replies_enabled' => false,
        ]);

        expect(countShownBy(TierlistsFeed::class))->toBe(0);
    });

    it('drops a deleted tier list', function () {
        $tierlist = publicCompletedTierlist();

        expect(countShownBy(TierlistsFeed::class))->toBe(1);

        $tierlist->delete();

        expect(countShownBy(TierlistsFeed::class))->toBe(0);
    });
});

describe('the reviews count', function () {
    it('counts a review once it is published', function () {
        $review = Review::factory()->public()->create();

        expect(countShownBy(ReviewsFeed::class))->toBe(0);

        (new PublishReview)->handle($review);

        expect(countShownBy(ReviewsFeed::class))->toBe(1);
    });

    it('drops a review that is made private', function () {
        $review = publicPublishedReview();

        expect(countShownBy(ReviewsFeed::class))->toBe(1);

        (new UpdateReview)->handle($review, [
            'name' => $review->name,
            'stars' => $review->stars,
            'body' => $review->body,
            'is_public' => false,
            'comments_enabled' => false,
            'comments_replies_enabled' => false,
        ]);

        expect(countShownBy(ReviewsFeed::class))->toBe(0);
    });
});

describe('a change still being written', function () {
    it('leaves the count alone until the change commits', function () {
        $tierlist = Tierlist::factory()->public()->create();

        expect(countShownBy(TierlistsFeed::class))->toBe(0);

        DB::transaction(function () use ($tierlist) {
            (new CompleteTierlist)->handle($tierlist);

            expect(cache()->has(Tierlist::EXPLORE_COUNT_CACHE_KEY))->toBeTrue();
        });

        expect(countShownBy(TierlistsFeed::class))->toBe(1);
    });
});

/**
 * The count a feed shows. Whoever looks first caches it for a day, so asking
 * twice around a change shows whether the change let go of the old number.
 */
function countShownBy(string $feed): int
{
    return Livewire::test($feed)->viewData(match ($feed) {
        RankingsFeed::class => 'totalRankings',
        TierlistsFeed::class => 'totalTierlists',
        ReviewsFeed::class => 'totalReviews',
    });
}
