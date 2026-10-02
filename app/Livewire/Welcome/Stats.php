<?php

namespace App\Livewire\Welcome;

use App\Models\LandingPageContent;
use App\Models\Playlist;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Song;
use App\Models\Tierlist;
use App\Models\User;
use Livewire\Component;

class Stats extends Component
{
    /**
     * How many public, finished records a newer kind needs before its card is shown.
     */
    public const int MINIMUM_SHOWN = 25;

    public static function getUserCount(): int
    {
        return cache()->remember('welcome:stats:users', now()->addDay(), fn () => User::query()->roundedUserCount());
    }

    public static function getRankingCount(): int
    {
        return cache()->remember('welcome:stats:rankings', now()->addDay(), fn () => Ranking::query()->publicRankedCount());
    }

    public static function getArtistCount(): int
    {
        return cache()->remember('welcome:stats:artists', now()->addDay(), fn () => Song::query()->rankedArtistCount());
    }

    public static function getPlaylistCount(): int
    {
        return cache()->remember('welcome:stats:playlists', now()->addDay(), fn () => Playlist::query()->rankedPlaylistCount());
    }

    public static function getTierlistCount(): int
    {
        return cache()->remember('welcome:stats:tierlists', now()->addDay(), fn () => Tierlist::query()->publicCompletedCount());
    }

    public static function getReviewCount(): int
    {
        return cache()->remember('welcome:stats:reviews', now()->addDay(), fn () => Review::query()->publicPublishedCount());
    }

    public static function hasEnoughTierlists(): bool
    {
        return cache()->remember('welcome:stats:tierlists:shown', now()->addDay(), fn () => Tierlist::query()->explorableCount() >= self::MINIMUM_SHOWN);
    }

    public static function hasEnoughReviews(): bool
    {
        return cache()->remember('welcome:stats:reviews:shown', now()->addDay(), fn () => Review::query()->explorableCount() >= self::MINIMUM_SHOWN);
    }

    public function render()
    {
        $content = LandingPageContent::cached();

        $colors = ['purple', 'green', 'blue'];

        $stats = collect([
            ['value' => self::getUserCount(), 'label' => $content->get('stats-users-label'), 'icon' => 'fa-users'],
            ['value' => self::getRankingCount(), 'label' => $content->get('stats-rankings-label'), 'icon' => 'fa-ranking-star'],
            self::hasEnoughTierlists() ? ['value' => self::getTierlistCount(), 'label' => $content->get('stats-tierlists-label'), 'icon' => 'fa-layer-group'] : null,
            self::hasEnoughReviews() ? ['value' => self::getReviewCount(), 'label' => $content->get('stats-reviews-label'), 'icon' => 'fa-pen-nib'] : null,
            ['value' => self::getArtistCount(), 'label' => $content->get('stats-artists-label'), 'icon' => 'fa-guitar'],
            ['value' => self::getPlaylistCount(), 'label' => $content->get('stats-playlists-label'), 'icon' => 'fa-headphones'],
        ])
            ->filter()
            ->values()
            ->map(fn (array $stat, int $index) => [...$stat, 'color' => $colors[$index % count($colors)]]);

        return view('livewire.welcome.stats', [
            'stats' => $stats,
        ]);
    }
}
