<?php

use App\Livewire\Reviews\ReviewPanel;
use App\Models\Review;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('the panel on the dashboard', function () {

    it('appears for somebody the feature is on for, offering every type', function () {
        actingAs(kyle());

        get(route('dashboard'))
            ->assertSee('Reviews')
            ->assertSee(route('reviews.create', ['type' => 'artist']), escape: false)
            ->assertSee(route('reviews.create', ['type' => 'album']), escape: false)
            ->assertSee(route('reviews.create', ['type' => 'track']), escape: false);
    });
});

describe('what the panel offers', function () {
    it('counts what has been used against the limit', function () {
        $user = kyle();
        Review::factory()->for($user)->count(2)->create();

        Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertSee('2 / 5')
            ->assertDontSee(route('billing'), escape: false);
    });

    it('sends a spent allowance to billing instead of to setup', function () {
        $user = userWithReviews(5, ['is_dev' => true]);

        Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertDontSee(route('reviews.create', ['type' => 'album']), escape: false)
            ->assertSee(route('billing'), escape: false);
    });

    it('says unlimited for a pro account', function () {
        $user = proUser(['is_dev' => true]);

        Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertDontSee(route('billing'), escape: false);
    });
});
