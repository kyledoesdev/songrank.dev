<?php

namespace App\Seo;

use Laravel\Head\Facades\Head;

final class WelcomePage
{
    public function handle(): void
    {
        Head::schema([
            '@context' => 'https://schema.org',
            '@type' => 'WebApplication',
            'name' => config('app.name'),
            'url' => config('app.url'),
            'description' => 'The best place to rank, review, and catalog your favorite artists\' tracks',
            'applicationCategory' => 'EntertainmentApplication',
            'operatingSystem' => 'All',
            'browserRequirements' => 'Requires a modern web browser',
            'offers' => [
                [
                    '@type' => 'Offer',
                    'price' => '0',
                    'priceCurrency' => 'USD',
                    'description' => 'Free tier with rankings, tier lists, reviews, and catalog',
                ],
                [
                    '@type' => 'Offer',
                    'price' => '10.00',
                    'priceCurrency' => 'USD',
                    'description' => 'Song Rank Pro — one-time purchase for unlimited lists and more',
                ],
            ],
            'author' => [
                '@type' => 'Person',
                'name' => 'Kyle',
                'url' => 'https://kyledoes.dev',
            ],
        ]);
    }
}
