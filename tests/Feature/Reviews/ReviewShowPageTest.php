<?php

use App\Enums\ShareTarget;
use App\Livewire\Reviews\ReviewShow;
use App\Models\Album;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('who can see a review', function () {
    it('shows a public, published review to anybody signed in', function () {
        $review = publicPublishedReview();

        actingAs(User::factory()->createOne())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertOk();
    });

    it('hides a private review from everybody but its owner', function () {
        $review = Review::factory()->published()->createOne();

        actingAs(User::factory()->createOne())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertNotFound();

        actingAs($review->user)
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertOk();
    });

    it('sends the owner of a draft to finish writing it', function () {
        $review = Review::factory()->public()->createOne();

        actingAs($review->user)
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertRedirect(route('review.edit', ['id' => $review->getKey()]));
    });

    it('hides a draft from everybody else', function () {
        $review = Review::factory()->public()->createOne();

        actingAs(User::factory()->createOne())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertNotFound();
    });

    it('opens for guests once it is public and published', function () {
        $review = publicPublishedReview();

        get(route('review', ['id' => $review->getKey()]))->assertOk();
    });
});

describe('the page', function () {
    it('credits the reviewer to visitors', function () {
        $review = publicPublishedReview();

        Livewire::actingAs(User::factory()->createOne())
            ->test(ReviewShow::class, ['id' => $review->getKey()])
            ->assertSee(route('profile', ['id' => $review->user->spotify_id]), escape: false);
    });

    it('shows the score with its half star', function () {
        $review = publicPublishedReview(['stars' => 7.5]);

        Livewire::test(ReviewShow::class, ['id' => $review->getKey()])
            ->assertSeeHtml($review->score().'<span')
            ->assertSeeHtml('fa-star-half-stroke');
    });

    it('renders the stored body', function () {
        $review = publicPublishedReview(['body' => '<h2>Verdict</h2><p>A <strong>great</strong> record.</p>']);

        get(route('review', ['id' => $review->getKey()]))
            ->assertSee('<h2>Verdict</h2>', escape: false)
            ->assertSee('<strong>great</strong>', escape: false);
    });

    it('links the subject back to Spotify', function () {
        $review = publicPublishedReview();

        get(route('review', ['id' => $review->getKey()]))
            ->assertSee($review->subject->spotifyUrl(), escape: false);
    });

    it('offers the edit link to the owner only', function () {
        $review = Review::factory()->published()->public()->createOne();
        $editUrl = route('review.edit', ['id' => $review->getKey()]);

        actingAs($review->user)
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertSee($editUrl, escape: false);

        actingAs(User::factory()->createOne())
            ->get(route('review', ['id' => $review->getKey()]))
            ->assertDontSee($editUrl, escape: false);
    });
});

describe('sharing', function () {
    it('unfurls into the subject, the score and an excerpt', function () {
        $review = currentsReview(['stars' => 8.5, 'body_text' => 'A great record.']);

        get(route('review', ['id' => $review->getKey()]))
            ->assertSee('<meta property="og:title" content="'.$review->shareTitle().'">', escape: false)
            ->assertSee('<meta property="og:description" content="A great record.">', escape: false)
            ->assertSee('<meta property="og:image" content="https://example.test/currents.png">', escape: false)
            ->assertSee('<meta name="twitter:card" content="summary_large_image">', escape: false);

        expect($review->shareTitle())
            ->toContain('Currents')
            ->toContain($review->score());
    });

    it('leaves the score out of the title when there is none', function () {
        $review = currentsReview(['stars' => null]);

        get(route('review', ['id' => $review->getKey()]))
            ->assertSee('<meta property="og:title" content="Currents">', escape: false);
    });

    it('describes a review with no words in it', function () {
        $review = currentsReview(['body_text' => null]);

        expect($review->shareDescription())->toContain($review->user->name);
    });

    it('offers a link to post on each network', function () {
        $review = currentsReview(['stars' => 8.5]);
        $url = route('review', ['id' => $review->getKey()]);

        $response = get($url);

        foreach (ShareTarget::cases() as $target) {
            $response->assertSee($target->intentUrl($review->shareText(), $url));
        }

        expect($review->shareText())->toContain($review->shareTitle());
    });

    it('gives a private review only the site\'s default preview and no share buttons', function () {
        $review = Review::factory()->published()->createOne(['stars' => 8.5]);
        $url = route('review', ['id' => $review->getKey()]);

        $response = actingAs($review->user)
            ->get($url)
            ->assertSee('<meta property="og:type" content="website">', escape: false);

        foreach (ShareTarget::cases() as $target) {
            $response->assertDontSee($target->intentUrl($review->shareText(), $url));
        }
    });
});

/**
 * A public review of Tame Impala's Currents, so the share copy is predictable.
 */
function currentsReview(array $attributes = []): Review
{
    $album = Album::factory()->createOne([
        'name' => 'Currents',
        'cover' => 'https://example.test/currents.png',
    ]);

    return publicPublishedReview(array_merge(['subject_id' => $album->getKey()], $attributes));
}
