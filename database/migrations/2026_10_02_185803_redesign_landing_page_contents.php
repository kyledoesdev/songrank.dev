<?php

use App\Models\LandingPageContent;
use Database\Seeders\LandingPageContentSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Copy the redesign rewrote, keyed by slug, with the text it replaces. A row
     * is only rewritten while it still holds that old text, so anything already
     * edited in the admin panel is left alone.
     *
     * @var array<string, string>
     */
    protected array $previousDefaults = [
        'hero-subtitle' => 'Rank you favorite artists\' tracks.',
        'how-it-works-subtitle' => 'Three steps to your definitive ranking',
        'community-explore-title' => 'Explore Rankings',
        'community-explore-text' => 'Browse public rankings from users across the world. Filter by artist, type, and more.',
        'community-comment-text' => 'Leave comments on rankings you agree (or disagree) with. Start conversations about the best tracks.',
        'community-react-title' => 'React to Rankings',
        'cta-subtitle' => 'Join the community and create your first ranking in minutes.',
    ];

    /**
     * @var list<string>
     */
    protected array $retiredSlugs = [
        'how-it-works-step-1-title',
        'how-it-works-step-1-text',
        'how-it-works-step-2-title',
        'how-it-works-step-2-text',
        'how-it-works-step-3-title',
        'how-it-works-step-3-text',
        'rank-types-artists-title',
        'rank-types-artists-text',
        'rank-types-playlists-title',
        'rank-types-playlists-text',
        'rank-types-audiobooks-title',
        'rank-types-audiobooks-text',
        'rank-types-podcasts-title',
        'rank-types-podcasts-text',
    ];

    public function up(): void
    {
        LandingPageContent::withTrashed()->whereIn('slug', $this->retiredSlugs)->forceDelete();

        $seeder = new LandingPageContentSeeder;
        $defaults = collect($seeder->contents)->pluck('text', 'slug');

        foreach ($this->previousDefaults as $slug => $previous) {
            LandingPageContent::query()
                ->where('slug', $slug)
                ->where('text', $previous)
                ->update(['text' => $defaults[$slug]]);
        }

        $seeder->run();

        cache()->forget(LandingPageContent::CACHE_KEY);
    }

    public function down(): void
    {
        //
    }
};
