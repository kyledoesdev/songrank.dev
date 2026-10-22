<?php

namespace App\Seo;

use App\Models\Ranking;
use App\Models\Song;
use Illuminate\Support\Carbon;
use Laravel\Head\Facades\Head;

final class RankingPage
{
    public function handle(Ranking $ranking): void
    {
        $url = route('ranking', ['id' => $ranking->getKey()]);
        $title = $ranking->name;
        $description = $ranking->shareDescription();
        $cover = $ranking->cover();

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
            Head::ogImage($cover, alt: $title);
            Head::twitterImage($cover, alt: $title);
        }

        Head::schema($this->schema($ranking, $url, $description, $cover));
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(Ranking $ranking, string $url, string $description, ?string $cover): array
    {
        $completedAt = $ranking->getRawOriginal('completed_at');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $ranking->name,
            'description' => $description,
            'url' => $url,
            'image' => $cover,
            'datePublished' => $completedAt ? Carbon::parse($completedAt)->toIso8601String() : null,
            'author' => [
                '@type' => 'Person',
                'name' => $ranking->user->name,
                'url' => route('profile', ['id' => $ranking->user->spotify_id]),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'itemListOrder' => 'https://schema.org/ItemListOrderAscending',
            'itemListElement' => $ranking->songs
                ->map(fn (Song $song, int $index) => array_filter([
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $song->title,
                    'url' => $song->spotify_song_id
                        ? "https://open.spotify.com/track/{$song->spotify_song_id}"
                        : null,
                ], fn ($value) => filled($value)))
                ->all(),
        ], fn ($value) => filled($value));
    }
}
