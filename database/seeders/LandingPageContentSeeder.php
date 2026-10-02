<?php

namespace Database\Seeders;

use App\Models\LandingPageContent;
use Illuminate\Database\Seeder;

class LandingPageContentSeeder extends Seeder
{
    /**
     * @var array<int, array{slug: string, name: string, text: string}>
     */
    public array $contents = [
        // Hero
        ['slug' => 'hero-title', 'name' => 'Hero Title', 'text' => 'Song - Rank'],
        ['slug' => 'hero-subtitle', 'name' => 'Hero Subtitle', 'text' => 'Rank your favorite artists\' tracks.'],
        ['slug' => 'hero-credit', 'name' => 'Hero Credit', 'text' => 'Made with ❤️ by'],
        ['slug' => 'hero-credit-name', 'name' => 'Hero Credit Name', 'text' => 'Kyle'],

        // Stats
        ['slug' => 'stats-users-label', 'name' => 'Stats Users Label', 'text' => 'Users Worldwide'],
        ['slug' => 'stats-rankings-label', 'name' => 'Stats Rankings Label', 'text' => 'Rankings Created'],
        ['slug' => 'stats-tierlists-label', 'name' => 'Stats Tier Lists Label', 'text' => 'Tier Lists Created'],
        ['slug' => 'stats-reviews-label', 'name' => 'Stats Reviews Label', 'text' => 'Reviews Written'],
        ['slug' => 'stats-artists-label', 'name' => 'Stats Artists Label', 'text' => 'Artists Ranked'],
        ['slug' => 'stats-playlists-label', 'name' => 'Stats Playlists Label', 'text' => 'Playlists Ranked'],

        // How It Works
        ['slug' => 'how-it-works-title', 'name' => 'How It Works Title', 'text' => 'How It Works'],
        ['slug' => 'how-it-works-subtitle', 'name' => 'How It Works Subtitle', 'text' => 'Three ways to put your music in order'],
        ['slug' => 'how-it-works-rankings-title', 'name' => 'How It Works Rankings Title', 'text' => 'Rankings'],
        ['slug' => 'how-it-works-rankings-text', 'name' => 'How It Works Rankings Text', 'text' => 'Pick an artist, playlist or podcast and choose between two tracks at a time. Our merge-sort algorithm keeps the comparisons to a minimum and hands you a complete, ordered list.'],
        ['slug' => 'how-it-works-tierlists-title', 'name' => 'How It Works Tier Lists Title', 'text' => 'Tier Lists'],
        ['slug' => 'how-it-works-tierlists-text', 'name' => 'How It Works Tier Lists Text', 'text' => 'Gather artists, albums or tracks and drag each one into a tier. Rename the tiers, reorder them, and publish the board when it looks right.'],
        ['slug' => 'how-it-works-reviews-title', 'name' => 'How It Works Reviews Title', 'text' => 'Reviews'],
        ['slug' => 'how-it-works-reviews-text', 'name' => 'How It Works Reviews Text', 'text' => 'Score an artist, album or track out of ten in half stars, then write about why. Save it as a draft until you are ready to publish.'],

        // Everything You Can Rank
        ['slug' => 'rank-types-title', 'name' => 'Everything You Can Rank Title', 'text' => 'Everything You Can Rank'],
        ['slug' => 'rank-types-subtitle', 'name' => 'Everything You Can Rank Subtitle', 'text' => 'More than just songs'],
        ['slug' => 'rank-types-rankings-artist', 'name' => 'Rank Types Ranking Artist Text', 'text' => 'Rank an artist\'s entire catalog, features included.'],
        ['slug' => 'rank-types-rankings-playlist', 'name' => 'Rank Types Ranking Playlist Text', 'text' => 'Find out which tracks deserve the top spot in your go-to mixes.'],
        ['slug' => 'rank-types-rankings-show', 'name' => 'Rank Types Ranking Show Text', 'text' => 'Rank podcast episodes to find the best starting points.'],
        ['slug' => 'rank-types-tierlists-artist', 'name' => 'Rank Types Tier List Artist Text', 'text' => 'Sort a genre, a scene or a festival lineup.'],
        ['slug' => 'rank-types-tierlists-album', 'name' => 'Rank Types Tier List Album Text', 'text' => 'Tier a discography, album by album.'],
        ['slug' => 'rank-types-tierlists-track', 'name' => 'Rank Types Tier List Track Text', 'text' => 'Mix tracks from anywhere onto one board.'],
        ['slug' => 'rank-types-reviews-artist', 'name' => 'Rank Types Review Artist Text', 'text' => 'Sum up an artist\'s whole body of work.'],
        ['slug' => 'rank-types-reviews-album', 'name' => 'Rank Types Review Album Text', 'text' => 'Give a record the write-up it deserves.'],
        ['slug' => 'rank-types-reviews-track', 'name' => 'Rank Types Review Track Text', 'text' => 'Zoom in on the one song you can\'t stop playing.'],

        // Community
        ['slug' => 'community-title', 'name' => 'Community Title', 'text' => 'Share, Explore & React'],
        ['slug' => 'community-explore-title', 'name' => 'Community Explore Title', 'text' => 'Explore'],
        ['slug' => 'community-explore-text', 'name' => 'Community Explore Text', 'text' => 'Browse public rankings, tier lists and reviews from users across the world.'],
        ['slug' => 'community-share-title', 'name' => 'Community Share Title', 'text' => 'Share'],
        ['slug' => 'community-share-text', 'name' => 'Community Share Text', 'text' => 'Post your finished lists and reviews to X and Bluesky, or copy a link to send anywhere.'],
        ['slug' => 'community-embed-title', 'name' => 'Community Embed Title', 'text' => 'Embed'],
        ['slug' => 'community-embed-text', 'name' => 'Community Embed Text', 'text' => 'Drop a finished tier list onto your own site or blog with a single embed code.'],
        ['slug' => 'community-comment-title', 'name' => 'Community Comment Title', 'text' => 'Comment & Discuss'],
        ['slug' => 'community-comment-text', 'name' => 'Community Comment Text', 'text' => 'Leave a comment on anything you agree (or disagree) with, and start a conversation about the music.'],
        ['slug' => 'community-react-title', 'name' => 'Community React Title', 'text' => 'React'],
        ['slug' => 'community-react-text', 'name' => 'Community React Text', 'text' => 'Use reactions to quickly share your take. Agree? Disagree? Let the community know.'],

        // Pricing
        ['slug' => 'pricing-title', 'name' => 'Pricing Title', 'text' => 'Pricing'],
        ['slug' => 'pricing-subtitle', 'name' => 'Pricing Subtitle', 'text' => 'Free to start. One payment for Pro, no subscription.'],

        // About Developer
        ['slug' => 'about-dev-title', 'name' => 'About Developer Title', 'text' => 'Built by Kyle'],
        ['slug' => 'about-dev-text', 'name' => 'About Developer Text', 'text' => 'SongRank is designed, developed, and maintained by Kyle — a solo developer who loves music, making playlists and ranking and comparing artists\' discography. I couldn\'t find a better way to rank my favorite artists\' tracks anywhere else so I decided to build it myself.'],

        // Call to Action
        ['slug' => 'cta-title', 'name' => 'Call to Action Title', 'text' => 'Ready to Rank?'],
        ['slug' => 'cta-subtitle', 'name' => 'Call to Action Subtitle', 'text' => 'Join the community and create your first ranking, tier list or review in minutes.'],
    ];

    /**
     * Only fills in missing slugs, so copy edited in the admin panel survives a re-run.
     */
    public function run(): void
    {
        foreach ($this->contents as $content) {
            LandingPageContent::withTrashed()->firstOrCreate(
                ['slug' => $content['slug']],
                $content
            );
        }
    }
}
