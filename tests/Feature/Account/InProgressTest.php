<?php

use App\Livewire\Dashboard\InProgress;
use App\Livewire\Tierlist\Card as TierlistCard;
use App\Livewire\Tierlist\TierlistPanel;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\TierlistItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

describe('getting back to a list', function () {
    it('lists unfinished ones under pick up where you left off, not in the setup panel', function () {
        $user = User::factory()->createOne();
        $tierlist = Tierlist::factory()->for($user)->create();
        $url = route('tierlist', ['id' => $tierlist->getKey()]);

        Livewire::actingAs($user)
            ->test(TierlistPanel::class)
            ->assertDontSee($url, escape: false);

        $component = Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSee($url, escape: false);

        expect($component->instance()->tierlists->modelKeys())->toBe([$tierlist->getKey()]);
    });

    it('keeps finished lists out of it, the way finished rankings are', function () {
        $user = User::factory()->createOne();
        $unfinished = Tierlist::factory()->for($user)->create();
        Tierlist::factory()->for($user)->complete()->create();

        $component = Livewire::actingAs($user)->test(InProgress::class);

        expect($component->instance()->tierlists->modelKeys())->toBe([$unfinished->getKey()]);
    });

    it('leaves other people lists out of it', function () {
        $user = User::factory()->createOne();
        $mine = Tierlist::factory()->for($user)->create();
        Tierlist::factory()->create();

        $component = Livewire::actingAs($user)->test(InProgress::class);

        expect($component->instance()->tierlists->modelKeys())->toBe([$mine->getKey()]);
    });

    it('draws the same card as rankings and reviews, with what is placed so far', function () {
        $user = User::factory()->createOne();
        $tierlist = Tierlist::factory()->for($user)->create();
        $artist = Artist::factory()->create();

        TierlistItem::factory()
            ->inTier($tierlist->placementTiers()->first())
            ->create(['entryable_id' => $artist->getKey()]);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSeeLivewire(TierlistCard::class)
            ->assertSee($artist->cover(), escape: false);
    });

    it('shows rankings and tier lists together', function () {
        $user = User::factory()->createOne();
        $ranking = Ranking::factory()->for($user)->create(['is_ranked' => false]);
        $tierlist = Tierlist::factory()->for($user)->create();

        $component = Livewire::actingAs($user)->test(InProgress::class);

        expect(inProgressPage($component))->toEqualCanonicalizing([inProgressKey($ranking), inProgressKey($tierlist)]);
    });

    it('stays away entirely when there is nothing to pick up', function () {
        $component = Livewire::actingAs(User::factory()->createOne())->test(InProgress::class);

        expect($component->instance()->hasAnything())->toBeFalse();
    });
});

describe('paging through it', function () {
    it('shows six at a time across every kind, most recently touched first', function () {
        $user = User::factory()->createOne();
        $recent = collect();

        foreach (range(1, 3) as $day) {
            $recent->push(Ranking::factory()->for($user)->create(['is_ranked' => false, 'updated_at' => now()->subDays($day)]));
            $recent->push(Tierlist::factory()->for($user)->create(['updated_at' => now()->subDays($day)]));
        }

        $draft = Review::factory()->for($user)->create(['updated_at' => now()->subDays(10)]);

        $component = Livewire::actingAs($user)->test(InProgress::class);

        expect($component->instance()->items->total())->toBe(7)
            ->and($component->instance()->items->lastPage())->toBe(2)
            ->and(inProgressPage($component))->toEqualCanonicalizing($recent->map(fn (Model $record) => inProgressKey($record))->all());

        $component->call('nextPage', 'in-progress');

        expect(inProgressPage($component))->toBe([inProgressKey($draft)]);
    });

    it('does not show page links when everything fits on one page', function () {
        $user = User::factory()->createOne();
        Ranking::factory()->for($user)->count(6)->create(['is_ranked' => false]);

        $component = Livewire::actingAs($user)->test(InProgress::class);

        expect($component->instance()->items->hasPages())->toBeFalse();
    });
});

describe('getting back to a draft', function () {
    it('lists drafts under pick up where you left off, linking to the editor', function () {
        $user = User::factory()->createOne();
        $review = Review::factory()->for($user)->createOne();

        $component = Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSee(route('review.edit', ['id' => $review->getKey()]), escape: false);

        expect($component->instance()->reviews->modelKeys())->toBe([$review->getKey()]);
    });

    it('keeps published reviews out of it', function () {
        $user = User::factory()->createOne();
        $draft = Review::factory()->for($user)->createOne();
        Review::factory()->for($user)->published()->createOne();

        $component = Livewire::actingAs($user)->test(InProgress::class);

        expect($component->instance()->reviews->modelKeys())->toBe([$draft->getKey()]);
    });

    it('leaves other people drafts out of it', function () {
        $user = User::factory()->createOne();
        $mine = Review::factory()->for($user)->createOne();
        Review::factory()->createOne();

        $component = Livewire::actingAs($user)->test(InProgress::class);

        expect($component->instance()->reviews->modelKeys())->toBe([$mine->getKey()]);
    });
});

/**
 * What the current page of the in-progress list holds, as kind and id.
 *
 * @return list<string>
 */
function inProgressPage(Testable $component): array
{
    return $component->instance()->items->getCollection()
        ->map(fn (Model $record) => inProgressKey($record))
        ->all();
}

function inProgressKey(Model $record): string
{
    return $record::class.':'.$record->getKey();
}
