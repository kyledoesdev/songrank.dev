<?php

use App\Livewire\Reviews\ReviewPanel;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('the panel on the dashboard', function () {

    it('appears on the dashboard, offering every type', function () {
        actingAs(User::factory()->createOne());

        get(route('dashboard'))
            ->assertSee(route('reviews.create', ['type' => 'artist']), escape: false)
            ->assertSee(route('reviews.create', ['type' => 'album']), escape: false)
            ->assertSee(route('reviews.create', ['type' => 'track']), escape: false);
    });
});

describe('what the panel offers', function () {
    it('counts what has been used against the limit', function () {
        $user = User::factory()->createOne();
        Review::factory()->for($user)->count(2)->create();

        $component = Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertDontSee(route('billing'), escape: false);

        expect($component->instance()->count())->toBe(2);
    });

    it('sends a spent allowance to billing instead of to setup', function () {
        $user = userWithReviews(config('billing.review_limits.free'));

        Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertDontSee(route('reviews.create', ['type' => 'album']), escape: false)
            ->assertSee(route('billing'), escape: false);
    });

    it('says unlimited for a pro account', function () {
        $user = proUser();

        Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertDontSee(route('billing'), escape: false);
    });
});
