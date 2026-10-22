<?php

namespace App\Http\Controllers;

use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap', now()->addHour(), fn () => $this->build());

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    private function build(): string
    {
        $urls = collect()
            ->merge($this->staticPages())
            ->merge($this->rankings())
            ->merge($this->tierlists())
            ->merge($this->reviews())
            ->merge($this->profiles());

        $entries = $urls->map(fn (array $entry) => $this->urlEntry($entry))->implode("\n");

        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
        {$entries}
        </urlset>
        XML;
    }

    /**
     * @return list<array{loc: string, changefreq: string, priority: string, lastmod?: string}>
     */
    private function staticPages(): array
    {
        return [
            ['loc' => route('welcome'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('about'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('explore'), 'changefreq' => 'daily', 'priority' => '0.9'],
            ['loc' => route('leaderboards'), 'changefreq' => 'daily', 'priority' => '0.8'],
            ['loc' => route('faq'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('privacy'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => route('terms'), 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];
    }

    /**
     * @return Collection<int, array{loc: string, changefreq: string, priority: string, lastmod: string}>
     */
    private function rankings(): Collection
    {
        return Ranking::query()
            ->public()
            ->completed()
            ->select('id', 'completed_at', 'updated_at')
            ->get()
            ->map(fn (Ranking $ranking) => [
                'loc' => route('ranking', ['id' => $ranking->getKey()]),
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'lastmod' => Carbon::parse($ranking->completed_at ?? $ranking->updated_at)->toDateString(),
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, changefreq: string, priority: string, lastmod: string}>
     */
    private function tierlists(): Collection
    {
        return Tierlist::query()
            ->public()
            ->completed()
            ->select('id', 'completed_at', 'updated_at')
            ->get()
            ->map(fn (Tierlist $tierlist) => [
                'loc' => route('tierlist', ['id' => $tierlist->getKey()]),
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'lastmod' => Carbon::parse($tierlist->completed_at ?? $tierlist->updated_at)->toDateString(),
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, changefreq: string, priority: string, lastmod: string}>
     */
    private function reviews(): Collection
    {
        return Review::query()
            ->public()
            ->published()
            ->select('id', 'published_at', 'updated_at')
            ->get()
            ->map(fn (Review $review) => [
                'loc' => route('review', ['id' => $review->getKey()]),
                'changefreq' => 'weekly',
                'priority' => '0.6',
                'lastmod' => Carbon::parse($review->published_at ?? $review->updated_at)->toDateString(),
            ]);
    }

    /**
     * @return Collection<int, array{loc: string, changefreq: string, priority: string}>
     */
    private function profiles(): Collection
    {
        return User::query()
            ->where(fn ($query) => $query
                ->whereHas('rankings', fn ($q) => $q->public()->completed())
                ->orWhereHas('tierlists', fn ($q) => $q->public()->completed())
                ->orWhereHas('reviews', fn ($q) => $q->public()->published()))
            ->select('id', 'spotify_id')
            ->get()
            ->map(fn (User $user) => [
                'loc' => route('profile', ['id' => $user->spotify_id]),
                'changefreq' => 'weekly',
                'priority' => '0.5',
            ]);
    }

    private function urlEntry(array $entry): string
    {
        $loc = htmlspecialchars($entry['loc']);
        $lastmod = isset($entry['lastmod']) ? "\n    <lastmod>{$entry['lastmod']}</lastmod>" : '';

        return <<<XML
            <url>
                <loc>{$loc}</loc>{$lastmod}
                <changefreq>{$entry['changefreq']}</changefreq>
                <priority>{$entry['priority']}</priority>
            </url>
        XML;
    }
}
