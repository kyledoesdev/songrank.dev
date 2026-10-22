<?php

namespace App\Seo;

use App\Models\User;
use Laravel\Head\Facades\Head;

final class ProfilePage
{
    public function handle(User $user): void
    {
        $name = possessive($user->name);
        $description = "{$name} profile on SongRank with rankings, tier lists, and reviews";
        $url = route('profile', ['id' => $user->spotify_id]);

        Head::description($description);
        Head::canonical($url);

        Head::og(
            title: "{$name} Profile",
            description: $description,
            url: $url,
        );

        Head::twitter(
            title: "{$name} Profile",
            description: $description,
        );

        if (filled($user->avatar)) {
            Head::ogImage($user->avatar, alt: $user->name);
            Head::twitterImage($user->avatar, alt: $user->name);
        }

        Head::schema($this->schema($user, $url, $description));
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(User $user, string $url, string $description): array
    {
        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'name' => "{$user->name} on SongRank",
            'description' => $description,
            'url' => $url,
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $user->name,
                'url' => $url,
                'image' => $user->avatar,
            ], fn ($value) => filled($value)),
        ], fn ($value) => filled($value));
    }
}
