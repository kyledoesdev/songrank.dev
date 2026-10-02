<?php

use App\Enums\ReviewType;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Models\Review;
use Livewire\Livewire;

describe('review table', function () {
    test('renders the table', function () {
        $review = Review::factory()->published()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListReviews::class)
            ->assertCanSeeTableRecords([$review])
            ->assertOk();
    });

    test('shows the score out of ten and the subject', function () {
        $review = Review::factory()->published()->createOne(['stars' => 8.5]);

        Livewire::actingAs(kyle())
            ->test(ListReviews::class)
            ->assertSee('8.5/10')
            ->assertSee($review->subject->name());
    });

    test('hides drafts by default', function () {
        $published = Review::factory()->published()->createOne();
        $draft = Review::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListReviews::class)
            ->assertCanSeeTableRecords([$published])
            ->assertCanNotSeeTableRecords([$draft]);
    });

    test('shows drafts when the filter is toggled off', function () {
        $published = Review::factory()->published()->createOne();
        $draft = Review::factory()->createOne();

        Livewire::actingAs(kyle())
            ->test(ListReviews::class)
            ->filterTable('hide_incomplete', false)
            ->assertCanSeeTableRecords([$published, $draft]);
    });

    test('filters by type', function () {
        $album = Review::factory()->published()->ofType(ReviewType::ALBUM)->createOne();
        $track = Review::factory()->published()->ofType(ReviewType::TRACK)->createOne();

        Livewire::actingAs(kyle())
            ->test(ListReviews::class)
            ->filterTable('type', ReviewType::ALBUM->value)
            ->assertCanSeeTableRecords([$album])
            ->assertCanNotSeeTableRecords([$track]);
    });

    test('shows deleted reviews through the trashed filter', function () {
        $review = Review::factory()->published()->createOne();
        $review->delete();

        Livewire::actingAs(kyle())
            ->test(ListReviews::class)
            ->assertCanNotSeeTableRecords([$review])
            ->filterTable('trashed', true)
            ->assertCanSeeTableRecords([$review]);
    });
});
