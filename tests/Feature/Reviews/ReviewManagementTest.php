<?php

use App\Livewire\Reviews\Card as ReviewCard;
use App\Livewire\Reviews\EditReview;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('reaching the edit page', function () {
    it('opens for the owner', function () {
        $review = Review::factory()->for(kyle())->createOne();

        actingAs($review->user)
            ->get(route('review.edit', ['id' => $review->getKey()]))
            ->assertOk();
    });

    it('is not there for anybody else', function () {
        $review = publicPublishedReview();

        actingAs(kyle())
            ->get(route('review.edit', ['id' => $review->getKey()]))
            ->assertNotFound();
    });

    it('is not there while the feature is off', function () {
        $review = Review::factory()->createOne();

        actingAs($review->user)
            ->get(route('review.edit', ['id' => $review->getKey()]))
            ->assertNotFound();
    });

    it('sends a guest away', function () {
        $review = publicPublishedReview();

        get(route('review.edit', ['id' => $review->getKey()]))->assertRedirect();
    });

    it('starts from what is stored', function () {
        $review = Review::factory()->for(kyle())->createOne([
            'name' => 'Stored Name',
            'stars' => 6.5,
            'body' => '<p>Stored body</p>',
            'is_public' => true,
        ]);

        editReview($review)
            ->assertSet('form.name', 'Stored Name')
            ->assertSet('form.is_public', '1')
            ->assertSet('stars', 6.5)
            ->assertSet('body', '<p>Stored body</p>');
    });
});

describe('writing a draft', function () {
    it('saves the score and a cleaned body', function () {
        $review = Review::factory()->for(kyle())->createOne();

        $component = editReview($review)
            ->call('setStars', 8.5)
            ->set('body', '<h2>Verdict</h2><p>Great.</p><script>alert(1)</script>')
            ->call('save')
            ->assertHasNoErrors();

        expect(alertsFrom($component)->pluck('title'))->toContain('Draft saved.');

        $review->refresh();

        expect($review->stars)->toBe(8.5)
            ->and($review->body)->toBe('<h2>Verdict</h2><p>Great.</p>')
            ->and($review->body_text)->toBe('Verdict Great.')
            ->and($review->is_published)->toBeFalse();
    });

    it('lets the score be cleared', function () {
        $review = Review::factory()->for(kyle())->createOne(['stars' => 4]);

        editReview($review)
            ->call('clearStars')
            ->call('save');

        expect($review->fresh()->stars)->toBeNull();
    });

    it('wants some words in the body', function () {
        $review = Review::factory()->for(kyle())->createOne();

        editReview($review)
            ->set('body', '<p></p>')
            ->call('save')
            ->assertHasErrors(['body']);
    });

    it('keeps the score between zero and ten', function () {
        $review = Review::factory()->for(kyle())->createOne();

        editReview($review)
            ->set('stars', 10.5)
            ->call('save')
            ->assertHasErrors(['stars' => 'max']);
    });

    it('needs a name no longer than sixty characters', function () {
        $review = Review::factory()->for(kyle())->createOne();

        editReview($review)
            ->set('form.name', str_repeat('a', 61))
            ->call('save')
            ->assertHasErrors(['form.name' => 'max']);
    });
});

describe('publishing', function () {
    it('asks first, and warns a free account it is final', function () {
        $review = Review::factory()->for(kyle())->createOne();

        $confirm = alertsFrom(editReview($review)->call('confirmPublish'), 'confirm')->first();

        expect($confirm['action'])->toBe('publish')
            ->and($confirm['message'])->toBe("Once you publish it, you won't be able to change the review's content.");
    });

    it('does not warn a pro account, who can keep editing', function () {
        $review = Review::factory()->for(proUser(['is_dev' => true]))->createOne();

        $confirm = alertsFrom(editReview($review)->call('confirmPublish'), 'confirm')->first();

        expect($confirm['message'])->toBe('');
    });

    it('publishes and opens the review', function () {
        Carbon::setTestNow('2026-10-01 12:00:00');

        $review = Review::factory()->for(kyle())->createOne();

        editReview($review)
            ->call('setStars', 9)
            ->call('publish')
            ->assertRedirect(route('review', ['id' => $review->getKey()]));

        $review->refresh();

        expect($review->is_published)->toBeTrue()
            ->and($review->getAttributes()['published_at'])->toBe('2026-10-01 12:00:00')
            ->and($review->stars)->toBe(9.0);
    });

    it('will not publish an empty review', function () {
        $review = Review::factory()->for(kyle())->createOne();

        editReview($review)
            ->set('body', '')
            ->call('publish')
            ->assertHasErrors(['body']);

        expect($review->fresh()->is_published)->toBeFalse();
    });
});

describe('editing once published', function () {
    it('locks a free account out of the content', function () {
        $review = Review::factory()->for(kyle())->published()->createOne([
            'stars' => 7,
            'body' => '<p>Original</p>',
        ]);

        editReview($review)
            ->assertSee('Want to change a published review?')
            ->set('stars', 2)
            ->set('body', '<p>Rewritten</p>')
            ->set('form.name', 'Renamed')
            ->call('save')
            ->assertHasNoErrors();

        $review->refresh();

        expect($review->stars)->toBe(7.0)
            ->and($review->body)->toBe('<p>Original</p>')
            ->and($review->name)->toBe('Renamed');
    });

    it('lets a pro account keep editing', function () {
        $review = Review::factory()->for(proUser(['is_dev' => true]))->published()->createOne();

        $component = editReview($review)
            ->assertDontSee('Want to change a published review?')
            ->call('setStars', 3)
            ->set('body', '<p>Changed my mind</p>')
            ->call('save');

        expect(alertsFrom($component)->pluck('title'))->toContain('Changes saved.');

        $review->refresh();

        expect($review->stars)->toBe(3.0)
            ->and($review->body)->toBe('<p>Changed my mind</p>');
    });

    it('never moves the published date', function () {
        $review = Review::factory()->for(proUser(['is_dev' => true]))->published()->createOne([
            'published_at' => '2026-01-01 00:00:00',
        ]);

        editReview($review)->call('save')->call('publish');

        expect($review->fresh()->getAttributes()['published_at'])->toBe('2026-01-01 00:00:00');
    });
});

describe('deleting', function () {
    it('removes the review from the edit page and goes to the profile', function () {
        $review = Review::factory()->for(kyle())->published()->createOne();

        editReview($review)
            ->call('destroy')
            ->assertRedirect(route('profile', ['id' => $review->user->spotify_id]))
            ->assertSessionHas('success', 'Review removed successfully.');

        expect(Review::find($review->getKey()))->toBeNull()
            ->and(Review::withTrashed()->find($review->getKey()))->not->toBeNull();
    });

    it('removes the review from its card', function () {
        $review = Review::factory()->for(kyle())->published()->createOne();

        Livewire::actingAs($review->user)
            ->test(ReviewCard::class, ['review' => $review])
            ->call('destroy')
            ->assertDispatched('reviews-updated');

        expect(Review::find($review->getKey()))->toBeNull();
    });

    it('will not let anybody else remove it from the card', function () {
        $review = publicPublishedReview();

        Livewire::actingAs(User::factory()->createOne(['is_dev' => true]))
            ->test(ReviewCard::class, ['review' => $review])
            ->call('destroy')
            ->assertForbidden();

        expect(Review::find($review->getKey()))->not->toBeNull();
    });

    it('clears the cached explore count for a review that was on it', function () {
        $review = Review::factory()->for(kyle())->published()->public()->createOne();

        cache()->put('explore:total-reviews', 1);

        editReview($review)->call('destroy');

        expect(cache()->has('explore:total-reviews'))->toBeFalse();
    });
});

function editReview(Review $review): Testable
{
    return Livewire::actingAs($review->user)
        ->test(EditReview::class, ['id' => $review->getKey()]);
}
