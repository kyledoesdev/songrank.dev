<?php

namespace App\Models;

use App\Enums\ReviewType;
use App\QueryBuilders\ReviewQueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Kyledoesdev\Essentials\Concerns\HasStatsAfterEvents;
use Spatie\Comments\Models\Concerns\HasComments;

#[UseEloquentBuilder(ReviewQueryBuilder::class)]
class Review extends Model
{
    use HasComments;
    use HasFactory;
    use HasStatsAfterEvents;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'subject_type',
        'subject_id',
        'name',
        'stars',
        'body',
        'body_text',
        'is_published',
        'is_public',
        'comments_enabled',
        'comments_replies_enabled',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ReviewType::class,
            'stars' => 'float',
            'is_published' => 'boolean',
            'is_public' => 'boolean',
            'comments_enabled' => 'boolean',
            'comments_replies_enabled' => 'boolean',
        ];
    }

    /* Relationships */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * What is being reviewed: an Artist, an Album or a Track.
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /* Attributes */

    public function getPublishedAtAttribute(): string
    {
        $published = $this->attributes['published_at'] ?? null;

        if (is_null($published)) {
            return 'Not published yet';
        }

        return Carbon::parse($published)->inUserTimezone()->diffForHumans();
    }

    public function getFormattedPublishedAtAttribute(): string
    {
        $published = $this->attributes['published_at'] ?? null;

        if (is_null($published)) {
            return 'Not published yet';
        }

        return Carbon::parse($published)->inUserTimezone()->format('M j, Y');
    }

    /* Methods */

    /**
     * The same value the stars show, read as a number out of ten. Trailing
     * zeroes are kept so a perfect ten reads "10.0" rather than "10".
     */
    public function score(): ?string
    {
        if (is_null($this->stars)) {
            return null;
        }

        return number_format($this->stars, 1);
    }

    public function hasScore(): bool
    {
        return ! is_null($this->stars);
    }

    /**
     * Half stars are drawn as halves, so a score of 7.5 is seven filled and
     * one half rather than eight.
     */
    public function fullStars(): int
    {
        return (int) floor($this->stars ?? 0);
    }

    public function hasHalfStar(): bool
    {
        return ! is_null($this->stars) && fmod($this->stars, 1.0) !== 0.0;
    }

    public function excerpt(int $length = 160): ?string
    {
        if (blank($this->body_text)) {
            return null;
        }

        return Str::limit($this->body_text, $length);
    }

    /**
     * What a share card leads with: the subject and the score, because that is
     * what somebody is actually passing around.
     */
    public function shareTitle(): string
    {
        $subject = $this->subject->name();

        return $this->hasScore()
            ? "{$subject} — {$this->score()}/10"
            : $subject;
    }

    public function shareDescription(): string
    {
        return $this->excerpt(180) ?? "{$this->type->label()} review by {$this->user->name} on ".config('app.name');
    }

    /**
     * The sentence a share button pre-fills. Networks append the link.
     */
    public function shareText(): string
    {
        return "{$this->shareTitle()} — my {$this->type->subjectLabel()} review on ".config('app.name');
    }

    public function canBeSeen(): bool
    {
        if ($this->user_id == Auth::id()) {
            return true;
        }

        return $this->is_public && $this->is_published;
    }

    public function canBeEdited(): bool
    {
        return $this->user_id === Auth::id();
    }

    /* Contracts */

    public function commentableName(): string
    {
        return $this->name;
    }

    public function commentUrl(): string
    {
        return route('review', ['id' => $this->getKey()]);
    }
}
