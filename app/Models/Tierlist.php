<?php

namespace App\Models;

use App\Enums\TierlistType;
use App\QueryBuilders\TierlistQueryBuilder;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\UseEloquentBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Kyledoesdev\Essentials\Concerns\HasStatsAfterEvents;
use Spatie\Comments\Models\Concerns\HasComments;

#[UseEloquentBuilder(TierlistQueryBuilder::class)]
class Tierlist extends Model
{
    use HasComments;
    use HasFactory;
    use HasStatsAfterEvents;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'source_type',
        'source_id',
        'name',
        'is_complete',
        'is_public',
        'comments_enabled',
        'comments_replies_enabled',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => TierlistType::class,
            'is_complete' => 'boolean',
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
     * Where the entries came from, when they came from one place — a playlist a
     * track list was imported from, an artist whose discography seeded an album
     * list. Provenance only; a list assembled from search has none.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Every tier including the bank, which always sorts first at position zero.
     */
    public function tiers(): HasMany
    {
        return $this->hasMany(Tier::class)->orderBy('position');
    }

    public function bank(): HasOne
    {
        return $this->hasOne(Tier::class)->where('is_bank', true);
    }

    public function items(): HasMany
    {
        return $this->hasMany(TierlistItem::class);
    }

    /* Attributes */

    public function getCompletedAtAttribute(): string
    {
        if (is_null($this->attributes['completed_at'])) {
            return 'In Progress';
        }

        return Carbon::parse($this->attributes['completed_at'])->diffForHumans();
    }

    public function getFormattedCompletedAtAttribute(): string
    {
        if (is_null($this->attributes['completed_at'])) {
            return 'In Progress';
        }

        return Carbon::parse($this->attributes['completed_at'])->inUserTimezone()->format('M d, Y g:i A T');
    }

    /* Helpers */

    /**
     * The placement tiers, bank excluded, in board order.
     *
     * @return Collection<int, Tier>
     */
    public function placementTiers(): Collection
    {
        return $this->tiers
            ->reject(fn (Tier $tier) => $tier->is_bank)
            ->values();
    }

    public function bankIsEmpty(): bool
    {
        return $this->bank->items->isEmpty();
    }

    /**
     * Every placed entry read the way the board reads — top tier left to right,
     * then down. The caller's index is the item's global rank, so no rank is
     * ever stored and none can drift from the board.
     *
     * @return Collection<int, TierlistItem>
     */
    public function rankedItems(): Collection
    {
        return $this->placementTiers()
            ->flatMap(fn (Tier $tier) => $tier->items->sortBy('position'))
            ->values();
    }

    /**
     * The top tier's entries, the part of a board people are actually sharing.
     *
     * @return Collection<int, TierlistItem>
     */
    public function topTierItems(): Collection
    {
        return $this->placementTiers()
            ->first(fn (Tier $tier) => $tier->items->isNotEmpty())
            ->items
            ->sortBy('position')
            ->values();
    }

    public function shareCover(): ?string
    {
        return $this->topTierItems()->first()->entryable->cover();
    }

    public function shareDescription(): string
    {
        $names = $this->topTierItems()
            ->take(3)
            ->map(fn (TierlistItem $item) => $item->entryable->name())
            ->join(', ');

        return "Top tier: {$names}. A tier list by {$this->type->label()} from {$this->user->name} on ".config('app.name');
    }

    /**
     * The sentence a share button pre-fills. Networks append the link.
     */
    public function shareText(): string
    {
        return "{$this->name} — my tier list by {$this->type->label()} on ".config('app.name');
    }

    /**
     * The snippet another site pastes in. The link under the frame is plain
     * markup on their page, so it counts as a link to the list where the
     * iframe's own links do not.
     */
    public function embedCode(): string
    {
        $embedUrl = route('tierlist.embed', ['id' => $this->getKey()]);
        $listUrl = route('tierlist', ['id' => $this->getKey()]);
        $name = e($this->name);
        $appName = e(config('app.name'));
        $appUrl = config('app.url');

        return <<<HTML
            <iframe src="{$embedUrl}" title="{$name}" width="100%" height="220" style="border:0;border-radius:12px;max-width:560px" loading="lazy"></iframe>
            <p style="font-size:12px;margin:4px 0 0"><a href="{$listUrl}">{$name}</a> &middot; a tier list on <a href="{$appUrl}">{$appName}</a></p>
            HTML;
    }

    public function canBeEdited(): bool
    {
        return $this->user_id === Auth::id();
    }

    public function canBeSeen(): bool
    {
        if ($this->user_id == Auth::id()) {
            return true;
        }

        return $this->is_public && $this->is_complete;
    }

    /* Contracts */

    public function commentableName(): string
    {
        return $this->name;
    }

    public function commentUrl(): string
    {
        return route('tierlist', ['id' => $this->getKey()]);
    }
}
