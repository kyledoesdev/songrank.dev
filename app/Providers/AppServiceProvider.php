<?php

namespace App\Providers;

use App\Enums\RankingType;
use App\Models\ApplicationDashboard as Seo;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Show;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Spotify\Provider;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DatabaseConnectionCountCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\EnvironmentCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('spotify', Provider::class);
        });

        DB::prohibitDestructiveCommands(app()->isProduction());

        Model::automaticallyEagerLoadRelationships();

        Vite::useAggressivePrefetching();

        Relation::morphMap([
            RankingType::ARTIST->value => Artist::class,
            RankingType::PLAYLIST->value => Playlist::class,
            RankingType::SHOW->value => Show::class,
        ]);

        $this->configureHealthChecks();
        $this->configureHead();
    }

    private function configureHealthChecks(): void
    {
        Health::checks([
            EnvironmentCheck::new(),
            DatabaseCheck::new(),
            DatabaseConnectionCountCheck::new()
                ->failWhenMoreConnectionsThan(100),
            DebugModeCheck::new(),
            OptimizedAppCheck::new(),
            ScheduleCheck::new()
                ->heartbeatMaxAgeInMinutes(15),
            UsedDiskSpaceCheck::new()
                ->warnWhenUsedSpaceIsAbovePercentage(90)
                ->failWhenUsedSpaceIsAbovePercentage(95),
        ]);
    }

    private function configureHead(): void
    {
        Head::defaults(function (HeadBuilder $head): HeadBuilder {
            return $head
                ->title(config('app.name'), suffix: ' | '.config('app.name'))
                ->description('Rank your favorite artists\' tracks')
                ->viewport('width=device-width, initial-scale=1')
                ->canonical()
                ->searchableByRobots()
                ->meta('author', 'kyledoesdev')
                ->when(! app()->runningUnitTests(), fn (HeadBuilder $head): HeadBuilder => $head
                    ->meta('keywords', cache()->remember('seo-terms', now()->addWeeks(1), fn () => Seo::query()->first()->seo_terms))
                )
                ->favicon(asset('favicon.ico'), 'image/x-icon')
                ->icon(asset('favicon-32x32.png'), 'image/png', '32x32')
                ->icon(asset('favicon-16x16.png'), 'image/png', '16x16')
                ->appleTouchIcon(asset('apple-touch-icon.png'), '180x180')
                ->manifest(asset('site.webmanifest'))
                ->og(
                    type: OgType::Website,
                    siteName: config('app.name'),
                    image: asset('images/branding/og.png'),
                )
                ->ogImage(
                    url: asset('images/branding/og.png'),
                    alt: config('app.name'),
                    width: 1200,
                    height: 630,
                    secureUrl: secure_asset('images/branding/og.png'),
                )
                ->twitter(
                    card: TwitterCard::SummaryWithLargeImage,
                    site: '@kyledoesdev',
                    creator: '@kyledoesdev',
                    image: asset('images/branding/og.png'),
                )
                ->twitterImage(
                    url: asset('images/branding/og.png'),
                    alt: config('app.name'),
                );
        });

        Head::errors(fn ($errors) => $errors
            ->defaults(fn (HeadBuilder $head): HeadBuilder => $head
                ->hiddenFromRobots()
            )
        );
    }
}
