<?php

use App\Enums\ReviewType;
use App\Livewire\Reviews\Setup\AlbumSetup;
use App\Livewire\Reviews\Setup\TrackSetup;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

describe('the free allowance', function () {
    it('allows five reviews', function () {
        expect(User::factory()->createOne()->reviewLimit())->toBe(5)
            ->and(userWithReviews(4)->canCreateReview())->toBeTrue()
            ->and(userWithReviews(5)->canCreateReview())->toBeFalse();
    });

    it('counts every type against the same five', function () {
        $user = User::factory()->createOne();

        Review::factory()->for($user)->count(3)->ofType(ReviewType::ALBUM)->create();
        Review::factory()->for($user)->count(2)->ofType(ReviewType::TRACK)->create();

        expect($user->canCreateReview())->toBeFalse();
    });

    it('counts drafts as well as published reviews', function () {
        $user = User::factory()->createOne();

        Review::factory()->for($user)->count(3)->create();
        Review::factory()->for($user)->count(2)->published()->create();

        expect($user->canCreateReview())->toBeFalse();
    });

    it('frees a slot when a review is deleted', function () {
        $user = userWithReviews(5);

        $user->reviews()->first()->delete();

        expect($user->canCreateReview())->toBeTrue();
    });

    it('only counts that user\'s reviews', function () {
        $user = User::factory()->createOne();
        Review::factory()->count(5)->create();

        expect($user->canCreateReview())->toBeTrue();
    });
});

describe('the pro allowance', function () {
    it('has no limit', function () {
        $user = proUser();
        Review::factory()->for($user)->count(6)->create();

        expect($user->reviewLimit())->toBeNull()
            ->and($user->canCreateReview())->toBeTrue();
    });
});

describe('hitting the limit in setup', function () {
    it('swaps the search for an upgrade prompt', function () {
        Livewire::actingAs(userWithReviews(5, ['is_dev' => true]))
            ->test(AlbumSetup::class)
            ->assertSee('That is all your reviews used')
            ->assertSee(route('billing'))
            ->assertDontSee('Search for an album...');
    });

    it('stops a search from a stale page before it reaches Spotify', function () {
        Http::fake();

        $component = Livewire::actingAs(userWithReviews(5, ['is_dev' => true]))
            ->test(TrackSetup::class)
            ->set('searchTerm', 'currents')
            ->call('search');

        expect(alertsFrom($component)->pluck('title'))->toContain('You have used all your reviews');

        Http::assertNothingSent();
    });

    it('stops a review being started from a stale page', function () {
        $user = userWithReviews(5, ['is_dev' => true]);

        Livewire::actingAs($user)
            ->test(AlbumSetup::class)
            ->set('subject', albumEntry())
            ->call('startReview')
            ->assertNoRedirect();

        expect($user->reviews()->count())->toBe(5);
    });
});
