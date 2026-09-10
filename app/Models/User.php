<?php

namespace App\Models;

use App\Models\Concerns\HasRankingAllowance;
use App\QueryBuilders\UserQueryBuilder;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Kyledoesdev\Essentials\Concerns\HasStatsAfterEvents;
use Laravel\Cashier\Billable;
use Laravel\Pennant\Concerns\HasFeatures;
use Spatie\Comments\Models\Concerns\InteractsWithComments;
use Spatie\Comments\Models\Concerns\Interfaces\CanComment;
use Spatie\Comments\Support\CommentatorProperties;

#[UseEloquentBuilder(UserQueryBuilder::class)]
class User extends Authenticatable implements CanComment, FilamentUser
{
    use Billable;
    use HasFactory;
    use HasFeatures;
    use HasRankingAllowance;
    use HasStatsAfterEvents;
    use InteractsWithComments;
    use Notifiable;
    use SoftDeletes;

    protected $fillable = [
        'spotify_id',
        'name',
        'email',
        'avatar',
        'timezone',
        'ip_address',
        'user_agent',
        'user_platform',
        'user_packet',
        'external_token',
        'external_refresh_token',
        'stripe_id',
        'is_dev',
        'is_pro',
        'password',
    ];

    protected $hidden = [
        'remember_token',
        'stripe_id',
        'external_token',
        'external_refresh_token',
        'ip_address',
        'user_agent',
        'user_platform',
        'user_packet',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'user_packet' => 'object',
            'is_dev' => 'boolean',
            'is_pro' => 'boolean',
        ];
    }

    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'notifiable')->latest();
    }

    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class);
    }

    public function preferences(): HasOne
    {
        return $this->hasOne(UserPreference::class);
    }

    public function proLicenses(): HasMany
    {
        return $this->hasMany(ProLicense::class);
    }

    public function syncProStatus(): void
    {
        $isPro = $this->proLicenses()->active()->exists();

        static::withTrashed()
            ->whereKey($this->getKey())
            ->update(['is_pro' => $isPro]);

        $this->is_pro = $isPro;
        $this->syncOriginalAttribute('is_pro');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_dev;
    }

    /* Overrides vars available in comment components */
    public function commentatorProperties(): CommentatorProperties
    {
        return CommentatorProperties::email($this->email)
            ->name($this->name)
            ->avatar($this->avatar)
            ->add('spotify_id', $this->spotify_id);
    }
}
