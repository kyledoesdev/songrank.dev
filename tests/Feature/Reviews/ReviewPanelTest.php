<?php

use App\Livewire\Reviews\ReviewPanel;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('the panel on the dashboard', function () {
    it('stays off the dashboard while the feature is off', function () {
        actingAs(User::factory()->createOne());

        get(route('dashboard'))->assertDontSee('Say what you actually thought');
    });

    it('appears for somebody the feature is on for, offering every type', function () {
        actingAs(kyle());

        get(route('dashboard'))
            ->assertSee('Say what you actually thought')
            ->assertSee(route('reviews.create', ['type' => 'artist']), escape: false)
            ->assertSee(route('reviews.create', ['type' => 'album']), escape: false)
            ->assertSee(route('reviews.create', ['type' => 'track']), escape: false);
    });
});

describe('what the panel offers', function () {
    it('counts what has been written and what is left', function () {
        $user = kyle();
        Review::factory()->for($user)->count(2)->create();

        Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertSee('2 written')
            ->assertSee('3 left');
    });

    it('sends a spent allowance to billing instead of to setup', function () {
        $user = userWithReviews(5, ['is_dev' => true]);

        Livewire::actingAs($user)
            ->test(ReviewPanel::class)
            ->assertDontSee(route('reviews.create', ['type' => 'album']), escape: false)
            ->assertSee(route('billing'), escape: false)
            ->assertSee('0 left');
    });

    it('says unlimited for a pro account', function () {
        Livewire::actingAs(proUser(['is_dev' => true]))
            ->test(ReviewPanel::class)
            ->assertSee('unlimited left');
    });
});
