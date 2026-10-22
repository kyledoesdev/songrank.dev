<?php

namespace App\Seo;

use Illuminate\Support\Collection;
use Laravel\Head\Facades\Head;

final class LeaderboardsPage
{
    public function handle(Collection $topArtists): void
    {
        $description = 'See the most ranked artists, top creators, biggest rankings, and most reviewed music on SongRank';
        $url = route('leaderboards');

        Head::description($description);
        Head::canonical($url);

        Head::og(
            title: 'Leaderboards',
            description: $description,
            url: $url,
        );

        Head::twitter(
            title: 'Leaderboards',
            description: $description,
        );

        Head::schema($this->schema($topArtists, $url, $description));
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(Collection $topArtists, string $url, string $description): array
    {
        $items = $topArtists
            ->values()
            ->map(fn (array $artist, int $index) => array_filter([
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $artist['name'],
                'url' => $artist['spotify_url'],
            ], fn ($value) => filled($value)))
            ->all();

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => 'SongRank Leaderboards',
            'description' => $description,
            'url' => $url,
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'itemListElement' => $items,
        ], fn ($value) => filled($value));
    }
}
