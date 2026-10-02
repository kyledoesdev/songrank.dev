<?php

use App\Enums\ReviewType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Review;
use App\Models\Track;
use App\Models\User;

use function Pest\Laravel\actingAs;

describe('the explore feed', function () {
    it('shows only reviews that are public and published', function () {
        $explorable = publicPublishedReview();
        Review::factory()->published()->create();
        Review::factory()->public()->create();

        expect(Review::query()->forExplorePage()->pluck('id')->all())->toBe([$explorable->id]);
    });

    it('puts the most recently published review first', function () {
        $older = publicPublishedReview(['published_at' => now()->subWeek()]);
        $newer = publicPublishedReview(['published_at' => now()]);

        expect(Review::query()->forExplorePage()->pluck('id')->all())->toBe([$newer->id, $older->id]);
    });

    it('searches the review name', function () {
        $match = publicPublishedReview(['name' => 'A Psych Rock Masterpiece']);
        publicPublishedReview(['name' => 'Something Else', 'body_text' => 'nothing to see']);

        expect(Review::query()->forExplorePage('psych rock')->pluck('id')->all())->toBe([$match->id]);
    });

    it('searches what the review says', function () {
        $match = publicPublishedReview(['body_text' => 'the drums on this are unreal']);
        publicPublishedReview(['body_text' => 'nothing to see']);

        expect(Review::query()->forExplorePage('drums')->pluck('id')->all())->toBe([$match->id]);
    });

    it('searches the name of the subject, whatever its type', function () {
        $artist = publicPublishedReview([
            'type' => ReviewType::ARTIST->value,
            'subject_type' => (new Artist)->getMorphClass(),
            'subject_id' => Artist::factory()->createOne(['artist_name' => 'Local Natives'])->getKey(),
        ]);
        $album = publicPublishedReview([
            'subject_id' => Album::factory()->createOne(['name' => 'Local Natives Live'])->getKey(),
        ]);
        $track = publicPublishedReview([
            'type' => ReviewType::TRACK->value,
            'subject_type' => (new Track)->getMorphClass(),
            'subject_id' => Track::factory()->createOne(['name' => 'Local Natives Medley'])->getKey(),
        ]);
        publicPublishedReview(['name' => 'Elsewhere', 'body_text' => 'nothing to see']);

        expect(Review::query()->forExplorePage('local natives')->pluck('id')->sort()->values()->all())
            ->toBe([$artist->id, $album->id, $track->id]);
    });

    it('narrows to one type', function () {
        $album = publicPublishedReview();
        Review::factory()->published()->public()->ofType(ReviewType::TRACK)->create();

        expect(Review::query()->forExplorePage(type: ReviewType::ALBUM)->pluck('id')->all())->toBe([$album->id]);
    });

    it('counts what explore can show', function () {
        publicPublishedReview();
        publicPublishedReview();
        Review::factory()->published()->create();

        expect(Review::query()->explorableCount())->toBe(2);
    });
});

describe('the profile page', function () {
    it('shows the owner every review they have, drafts first', function () {
        $owner = User::factory()->createOne();
        $published = Review::factory()->for($owner)->published()->create(['published_at' => now()->subDay()]);
        $draft = Review::factory()->for($owner)->create();
        $private = Review::factory()->for($owner)->published()->create(['published_at' => now()]);

        actingAs($owner);

        expect(Review::query()->forProfilePage($owner)->pluck('id')->all())
            ->toBe([$draft->id, $private->id, $published->id]);
    });

    it('shows a visitor only the public, published ones', function () {
        $owner = User::factory()->createOne();
        $public = Review::factory()->for($owner)->published()->public()->create();
        Review::factory()->for($owner)->published()->create();
        Review::factory()->for($owner)->public()->create();

        actingAs(User::factory()->createOne());

        expect(Review::query()->forProfilePage($owner)->pluck('id')->all())->toBe([$public->id]);
    });
});

describe('the dashboard', function () {
    it('lists only that user\'s reviews, drafts first', function () {
        $owner = User::factory()->createOne();
        $published = Review::factory()->for($owner)->published()->create();
        $draft = Review::factory()->for($owner)->create();
        Review::factory()->create();

        expect(Review::query()->forDashboard($owner)->pluck('id')->all())->toBe([$draft->id, $published->id]);
    });
});
