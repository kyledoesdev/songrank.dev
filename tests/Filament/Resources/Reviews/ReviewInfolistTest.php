<?php

use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Models\Review;
use Livewire\Livewire;

describe('review infolist', function () {
    test('renders the view page for a published review', function () {
        $review = publicPublishedReview();

        Livewire::actingAs(kyle())
            ->test(ViewReview::class, ['record' => $review->getKey()])
            ->assertOk()
            ->assertSee(route('review', ['id' => $review->getKey()]), escape: false);
    });

    test('links the subject back to spotify', function () {
        $review = publicPublishedReview();

        Livewire::actingAs(kyle())
            ->test(ViewReview::class, ['record' => $review->getKey()])
            ->assertSee($review->subject->spotifyUrl(), escape: false);
    });

    test('renders the stored body as html', function () {
        $review = publicPublishedReview(['body' => '<h2>Verdict</h2><p>A <strong>great</strong> record.</p>']);

        Livewire::actingAs(kyle())
            ->test(ViewReview::class, ['record' => $review->getKey()])
            ->assertSeeHtml('<h2>Verdict</h2>')
            ->assertSeeHtml('<strong>great</strong>');
    });

    test('reads an unscored review as not scored', function () {
        $review = Review::factory()->published()->unscored()->createOne();

        Livewire::actingAs(kyle())
            ->test(ViewReview::class, ['record' => $review->getKey()])
            ->assertSchemaComponentStateSet('stars', null);
    });

    test('opens a deleted review', function () {
        $review = publicPublishedReview();
        $review->delete();

        Livewire::actingAs(kyle())
            ->test(ViewReview::class, ['record' => $review->getKey()])
            ->assertOk();
    });
});
