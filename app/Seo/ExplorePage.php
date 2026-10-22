<?php

namespace App\Seo;

use Laravel\Head\Facades\Head;

final class ExplorePage
{
    public function handle(): void
    {
        $description = 'Browse public rankings, tier lists, and reviews from the SongRank community';
        $url = route('explore');

        Head::description($description);
        Head::canonical($url);

        Head::og(
            title: 'Explore',
            description: $description,
            url: $url,
        );

        Head::twitter(
            title: 'Explore',
            description: $description,
        );

        Head::schema([
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Explore',
            'description' => $description,
            'url' => $url,
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
        ]);
    }
}
