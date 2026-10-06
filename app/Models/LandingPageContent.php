<?php

namespace App\Models;

use App\Observers\LandingPageContentObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

#[ObservedBy(LandingPageContentObserver::class)]
class LandingPageContent extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const string CACHE_KEY = 'landing-page-contents';

    protected $fillable = [
        'slug',
        'name',
        'text',
    ];

    /**
     * Every piece of copy keyed by slug, so a view reads `$content->get('hero-title')`.
     *
     * @return Collection<string, string>
     */
    public static function cached(): Collection
    {
        return cache()->remember(self::CACHE_KEY, now()->addDay(), fn () => self::query()->pluck('text', 'slug'));
    }
}
