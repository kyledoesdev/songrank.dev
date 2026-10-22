<?php

namespace App\Seo;

use App\Models\Review;
use Illuminate\Support\Carbon;
use Laravel\Head\Facades\Head;

final class ReviewPage
{
    public function handle(Review $review): void
    {
        $title = $review->shareTitle();
        $description = $review->shareDescription();
        $cover = $review->subject->cover();
        $url = route('review', ['id' => $review->getKey()]);

        Head::description($description);
        Head::canonical($url);

        Head::og(
            type: 'article',
            title: $title,
            description: $description,
            url: $url,
        );

        Head::twitter(
            card: filled($cover) ? 'summary_large_image' : 'summary',
            title: $title,
            description: $description,
        );

        if (filled($cover)) {
            Head::ogImage($cover, alt: $review->subject->name());
            Head::twitterImage($cover, alt: $review->subject->name());
        }

        Head::schema($this->schema($review, $url, $description, $cover));
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(Review $review, string $url, string $description, ?string $cover): array
    {
        $publishedAt = $review->getRawOriginal('published_at');

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'name' => $review->name,
            'description' => $description,
            'url' => $url,
            'image' => $cover,
            'datePublished' => $publishedAt ? Carbon::parse($publishedAt)->toIso8601String() : null,
            'author' => [
                '@type' => 'Person',
                'name' => $review->user->name,
                'url' => route('profile', ['id' => $review->user->spotify_id]),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'itemReviewed' => [
                '@type' => 'MusicRecording',
                'name' => $review->subject->name(),
                'url' => $review->subject->spotifyUrl(),
            ],
        ], fn ($value) => filled($value));

        if ($review->hasScore()) {
            $schema['reviewRating'] = [
                '@type' => 'Rating',
                'ratingValue' => $review->stars,
                'bestRating' => 10,
                'worstRating' => 0,
            ];
        }

        return $schema;
    }
}
