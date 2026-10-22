<?php

namespace App\Seo;

use Laravel\Head\Facades\Head;

final class AboutPage
{
    public function handle(): void
    {
        $description = 'Learn about SongRank, the best place to rank, review, and catalog your favorite music';
        $url = route('about');

        Head::description($description);
        Head::canonical($url);

        Head::og(
            title: 'About',
            description: $description,
            url: $url,
        );

        Head::twitter(
            title: 'About',
            description: $description,
        );
    }
}
