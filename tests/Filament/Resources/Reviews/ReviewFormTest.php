<?php

use App\Filament\Resources\Reviews\Pages\EditReview;
use Livewire\Livewire;

describe('review form', function () {
    test('saves the name and visibility settings', function () {
        $review = publicPublishedReview(['name' => 'Original']);

        Livewire::actingAs(kyle())
            ->test(EditReview::class, ['record' => $review->getKey()])
            ->fillForm([
                'name' => 'Renamed',
                'is_public' => false,
                'comments_enabled' => true,
                'comments_replies_enabled' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $review->refresh();

        expect($review->name)->toBe('Renamed');
        expect($review->is_public)->toBeFalse();
        expect($review->comments_enabled)->toBeTrue();
        expect($review->comments_replies_enabled)->toBeFalse();
    });

    test('leaves the body and score alone', function () {
        $review = publicPublishedReview(['stars' => 7.5, 'body' => '<p>Untouched</p>']);

        Livewire::actingAs(kyle())
            ->test(EditReview::class, ['record' => $review->getKey()])
            ->assertFormFieldDoesNotExist('body')
            ->assertFormFieldDoesNotExist('stars')
            ->fillForm(['name' => 'Renamed'])
            ->call('save');

        $review->refresh();

        expect($review->stars)->toBe(7.5);
        expect($review->body)->toBe('<p>Untouched</p>');
    });

    test('requires a name no longer than sixty characters', function () {
        $review = publicPublishedReview();

        Livewire::actingAs(kyle())
            ->test(EditReview::class, ['record' => $review->getKey()])
            ->fillForm(['name' => str_repeat('a', 61)])
            ->call('save')
            ->assertHasFormErrors(['name' => 'max']);

        Livewire::actingAs(kyle())
            ->test(EditReview::class, ['record' => $review->getKey()])
            ->fillForm(['name' => ''])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required']);
    });
});
