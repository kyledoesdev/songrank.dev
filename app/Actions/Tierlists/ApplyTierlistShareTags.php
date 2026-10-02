<?php

namespace App\Actions\Tierlists;

use App\Models\Tierlist;
use App\Models\TierlistItem;
use Illuminate\Support\Carbon;
use Laravel\Head\Facades\Head;

final class ApplyTierlistShareTags
{
    public function handle(Tierlist $tierlist): void
    {
        $url = route('tierlist', ['id' => $tierlist->getKey()]);
        $title = $tierlist->name;
        $description = $tierlist->shareDescription();
        $cover = $tierlist->shareCover();

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

        Head::schema($this->schema($tierlist, $url, $description, $cover));
    }

    /**
     * @return array<string, mixed>
     */
    private function schema(Tierlist $tierlist, string $url, string $description, ?string $cover): array
    {
        $completedAt = $tierlist->getRawOriginal('completed_at');

        return array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $tierlist->name,
            'description' => $description,
            'url' => $url,
            'image' => $cover,
            'datePublished' => $completedAt ? Carbon::parse($completedAt)->toIso8601String() : null,
            'author' => [
                '@type' => 'Person',
                'name' => $tierlist->user->name,
                'url' => route('profile', ['id' => $tierlist->user->spotify_id]),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => config('app.url'),
            ],
            'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
            'itemListElement' => $tierlist->rankedItems()
                ->filter(fn (TierlistItem $item) => $item->entryable)
                ->values()
                ->map(fn (TierlistItem $item, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item->entryable->name(),
                    'url' => $item->entryable->spotifyUrl(),
                ])
                ->all(),
        ], fn ($value) => filled($value));
    }
}
