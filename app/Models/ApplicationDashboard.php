<?php

namespace App\Models;

use App\Observers\ApplicationDashboardObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[ObservedBy(ApplicationDashboardObserver::class)]
class ApplicationDashboard extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const string SEO_TERMS_CACHE_KEY = 'seo-terms';

    protected $table = 'application_dashboard';

    protected $fillable = [
        'name',
        'about_page',
        'support_page',
        'version',
        'slideshow_speed',
        'seo_terms',
    ];

    /**
     * The meta keywords every page renders, cached because the head is built on every request.
     */
    public static function seoTerms(): string
    {
        return cache()->remember(self::SEO_TERMS_CACHE_KEY, now()->addWeek(), fn () => (string) self::query()->first()?->seo_terms);
    }
}
