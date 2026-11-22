<?php

use App\Livewire\Explorer\RankingsFeed;
use App\Livewire\Explorer\TierlistsFeed;
use App\Livewire\Leaderboards;
use App\Livewire\Navigation;
use App\Models\ProLicense;
use App\Models\User;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('the badge', function () {
    it('carries the date its owner went pro', function () {
        $pro = proUserSince('2025-03-04 12:00:00');

        $badge = proBadgeFor($pro);

        expect($badge)
            ->toContain('data-pro-badge')
            ->toContain($pro->formattedProSince());
    });

    it('dates pro in the viewer\'s timezone', function () {
        $pro = proUserSince('2025-03-04 02:00:00');

        actingAs(User::factory()->createOne(['timezone' => 'America/New_York']));

        expect($pro->formattedProSince())->toBe('Mar 3, 2025');
    });

    it('renders nothing for a free account', function () {
        expect(trim(proBadgeFor(proUser())))->not->toBe('')
            ->and(trim(proBadgeFor(User::factory()->createOne())))->toBe('');
    });

    it('goes away when its owner loses pro', function () {
        $pro = proUser();

        expect(trim(proBadgeFor($pro)))->not->toBe('')
            ->and(trim(proBadgeFor(revokePro($pro))))->toBe('');
    });
});

describe('where the badge shows', function () {
    beforeEach(function () {
        actingAs(User::factory()->createOne());
    });

    it('sits beside a pro member\'s name', function (string $surface) {
        get(proBadgeSurfaceUrl($surface, proUser()))
            ->assertOk()
            ->assertSee('data-pro-badge', false);

        get(proBadgeSurfaceUrl($surface, User::factory()->createOne()))
            ->assertOk()
            ->assertDontSee('data-pro-badge', false);
    })->with([
        'a profile',
        'a ranking',
        'a tier list',
        'a review',
        'a tier list embed',
    ]);

    it('marks only a pro author on an explore card', function (string $feed) {
        publishForProBadgeFeed($feed, User::factory()->createOne());

        Livewire::test($feed)->assertDontSee('data-pro-badge', false);

        publishForProBadgeFeed($feed, proUser());

        Livewire::test($feed)->assertSee('data-pro-badge', false);
    })->with([
        'rankings' => RankingsFeed::class,
        'tier lists' => TierlistsFeed::class,
    ]);

    it('marks a pro member\'s comment', function () {
        $review = publicPublishedReview(['comments_enabled' => true]);
        $url = route('review', ['id' => $review->getKey()]);

        $review->comment('a free member says hi', User::factory()->createOne());

        get($url)->assertDontSee('data-pro-badge', false);

        $review->comment('a pro member says hi', proUser());

        get($url)->assertSee('data-pro-badge', false);
    });
});

describe('the leaderboards', function () {
    it('mark a pro creator', function () {
        $pro = proUser();
        publicCompletedRanking(attributes: ['user_id' => $pro->getKey()]);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topCreators', fn ($entries) => $entries->first()['creator']->is($pro))
            ->assertSee('data-pro-badge', false);
    });

    it('mark the pro author of a big ranking', function () {
        $pro = proUser();
        publicCompletedRanking(attributes: ['user_id' => $pro->getKey()]);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('biggestRankings', fn ($entries) => $entries->first()['author']->is($pro));
    });

    it('leave free creators alone', function () {
        publicCompletedRanking();

        Livewire::test(Leaderboards::class)->assertDontSee('data-pro-badge', false);
    });

    it('cache no more of a member than the board shows', function () {
        publicCompletedRanking(attributes: ['user_id' => proUser()->getKey()]);

        Livewire::test(Leaderboards::class)
            ->assertViewHas('topCreators', fn ($entries) => ! array_key_exists('external_token', $entries->first()['creator']->getAttributes())
                && $entries->first()['creator']->is_pro)
            ->assertViewHas('biggestRankings', fn ($entries) => ! array_key_exists('external_token', $entries->first()['author']->getAttributes())
                && $entries->first()['author']->is_pro);
    });
});

describe('the navigation', function () {
    it('shows a pro member their own badge', function () {
        Livewire::actingAs(proUser())
            ->test(Navigation::class)
            ->assertSee('data-pro-badge', false);
    });

    it('shows a free member none', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Navigation::class)
            ->assertDontSee('data-pro-badge', false);
    });
});

/**
 * A pro member whose one licence was bought at $purchasedAt (UTC).
 */
function proUserSince(string $purchasedAt): User
{
    $user = User::factory()->createOne();

    ProLicense::factory()->active()->for($user)->create(['purchased_at' => $purchasedAt]);

    return $user->fresh();
}

function proBadgeFor(User $user): string
{
    return Blade::render('<x-pro-badge :user="$user" />', ['user' => $user]);
}

/**
 * A page that names $author, holding something of theirs where it needs to.
 */
function proBadgeSurfaceUrl(string $surface, User $author): string
{
    return match ($surface) {
        'a profile' => route('profile', ['id' => $author->spotify_id]),
        'a ranking' => route('ranking', ['id' => publicCompletedRanking(attributes: ['user_id' => $author->getKey()])->getKey()]),
        'a tier list' => route('tierlist', ['id' => publicCompletedTierlist(['user_id' => $author->getKey()])->getKey()]),
        'a review' => route('review', ['id' => publicPublishedReview(['user_id' => $author->getKey()])->getKey()]),
        'a tier list embed' => route('tierlist.embed', ['id' => publicCompletedTierlist(['user_id' => $author->getKey()])->getKey()]),
    };
}

function publishForProBadgeFeed(string $feed, User $author): void
{
    match ($feed) {
        RankingsFeed::class => publicCompletedRanking(attributes: ['user_id' => $author->getKey()]),
        TierlistsFeed::class => publicCompletedTierlist(['user_id' => $author->getKey()]),
    };
}
