<?php

namespace App\Providers;

use App\Actions\Billing\HandleStripeWebhook;
use App\Checks\StripeWebhookSecretCheck;
use App\Enums\RankingType;
use App\Models\ApplicationDashboard as Seo;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Show;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookReceived;
use Laravel\Head\Enums\OgType;
use Laravel\Head\Enums\TwitterCard;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;
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
        DB::prohibitDestructiveCommands(app()->isProduction());

        Model::automaticallyEagerLoadRelationships();

        Vite::useAggressivePrefetching();

        Event::listen(function (SocialiteWasCalled $event) {
            $event->extendSocialite('spotify', Provider::class);
        });

        Relation::morphMap([
            RankingType::ARTIST->value => Artist::class,
            RankingType::PLAYLIST->value => Playlist::class,
            RankingType::SHOW->value => Show::class,
        ]);

        $this->configureFeatures();
        $this->configureBilling();
        $this->configureHealthChecks();
        $this->configureHead();
        $this->configureDevTerminal();
    }

    private function configureFeatures(): void
    {
        Feature::discover();

        /* Unreleased features should look absent, not forbidden. */
        EnsureFeaturesAreActive::whenInactive(
            fn () => abort(404)
        );
    }

    private function configureBilling(): void
    {
        if (config('billing.tax.automatic')) {
            Cashier::calculateTaxes();
        }
        
        Event::listen(WebhookReceived::class, HandleStripeWebhook::class);
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
            StripeWebhookSecretCheck::new(),
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
                ->meta('keywords', rescue(fn () => cache()->remember('seo-terms', now()->addWeeks(1), fn () => Seo::query()->first()->seo_terms), '', false))
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

    private function configureDevTerminal(): void
    {
        if (! $this->app->runningInConsole() || $this->app->runningUnitTests() || $this->app->isProduction()) {
            return;
        }

        DevCommands::except('server');
        DevCommands::node('dev', 'vite')->yellow();
        DevCommands::artisan('queue:listen --tries=1 --timeout=0', 'queue')->purple();
        DevCommands::register(
            sprintf('stripe listen --forward-to %s/stripe/webhook --skip-verify', rtrim(config('app.url'), '/')),
            'stripe',
        )->green();
    }
}
