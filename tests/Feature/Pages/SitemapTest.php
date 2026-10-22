<?php

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;

use function Pest\Laravel\get;

describe('sitemap', function () {
    test('returns xml with correct content type', function () {
        get(route('sitemap'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/xml');
    });

    test('includes static pages', function () {
        $response = get(route('sitemap'))->assertOk();

        $xml = $response->getContent();

        expect($xml)
            ->toContain(route('welcome'))
            ->toContain(route('about'))
            ->toContain(route('explore'))
            ->toContain(route('leaderboards'))
            ->toContain(route('faq'))
            ->toContain(route('privacy'))
            ->toContain(route('terms'));
    });

    test('includes public finished rankings', function () {
        $ranking = publicCompletedRanking();

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)->toContain(route('ranking', ['id' => $ranking->getKey()]));
    });

    test('excludes private and unfinished rankings', function () {
        $private = Ranking::factory()->create(['is_public' => false, 'is_ranked' => true]);
        $unfinished = Ranking::factory()->create(['is_public' => true, 'is_ranked' => false]);

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)
            ->not->toContain(route('ranking', ['id' => $private->getKey()]))
            ->not->toContain(route('ranking', ['id' => $unfinished->getKey()]));
    });

    test('includes public completed tier lists', function () {
        $tierlist = publicCompletedTierlist();

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)->toContain(route('tierlist', ['id' => $tierlist->getKey()]));
    });

    test('excludes private and incomplete tier lists', function () {
        $private = Tierlist::factory()->complete()->create(['is_public' => false]);
        $incomplete = Tierlist::factory()->public()->create(['is_complete' => false]);

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)
            ->not->toContain(route('tierlist', ['id' => $private->getKey()]))
            ->not->toContain(route('tierlist', ['id' => $incomplete->getKey()]));
    });

    test('includes public published reviews', function () {
        $review = publicPublishedReview();

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)->toContain(route('review', ['id' => $review->getKey()]));
    });

    test('excludes private and draft reviews', function () {
        $private = Review::factory()->published()->create(['is_public' => false]);
        $draft = Review::factory()->public()->create(['is_published' => false]);

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)
            ->not->toContain(route('review', ['id' => $private->getKey()]))
            ->not->toContain(route('review', ['id' => $draft->getKey()]));
    });

    test('includes profiles with public records', function () {
        $ranking = publicCompletedRanking();

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)->toContain(route('profile', ['id' => $ranking->user->spotify_id]));
    });

    test('excludes profiles without public records', function () {
        $user = User::factory()->create();

        $xml = get(route('sitemap'))->assertOk()->getContent();

        expect($xml)->not->toContain(route('profile', ['id' => $user->spotify_id]));
    });

    test('is valid xml', function () {
        publicCompletedRanking();

        $xml = get(route('sitemap'))->assertOk()->getContent();

        $doc = new DOMDocument;
        expect($doc->loadXML($xml))->toBeTrue();
    });
});
