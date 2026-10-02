<?php

namespace App\QueryBuilders;

use App\Enums\ReviewType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Track;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ReviewQueryBuilder extends Builder
{
    public function forExplorePage(?string $search = null, ?ReviewType $type = null): static
    {
        return $this->newQuery()
            ->public()
            ->published()
            ->when($search, fn (Builder $query, string $search) => $query->whereNameOrSubjectLike($search))
            ->when($type, fn (Builder $query, ReviewType $type) => $query->ofType($type))
            ->with('user', 'subject')
            ->withCount('comments')
            ->orderBy('published_at', 'desc');
    }

    public function forProfilePage(User $user): static
    {
        return $this->newQuery()
            ->where('user_id', $user->getKey())
            ->when($user->getKey() !== Auth::id(), fn (Builder $query) => $query->public()->published())
            ->with('user', 'subject')
            ->withCount('comments')
            ->orderByRaw('published_at IS NULL DESC, published_at DESC');
    }

    public function forDashboard(User $user): static
    {
        return $this->newQuery()
            ->where('user_id', $user->getKey())
            ->with('subject')
            ->withCount('comments')
            ->orderByRaw('published_at IS NULL DESC, published_at DESC');
    }

    /**
     * A review is named by its author and is often not named after its subject
     * at all, so the review's own name has to be searchable alongside it.
     */
    public function whereNameOrSubjectLike(string $search): static
    {
        return $this->where(function (Builder $query) use ($search) {
            $query->where('name', 'LIKE', "%{$search}%")
                ->orWhere('body_text', 'LIKE', "%{$search}%")
                ->orWhereHasMorph('subject', [Artist::class, Album::class, Track::class],
                    fn (Builder $query, string $type) => $query->where(
                        $type === Artist::class ? 'artist_name' : 'name',
                        'LIKE',
                        "%{$search}%",
                    ),
                );
        });
    }

    public function ofType(ReviewType $type): static
    {
        return $this->where('type', $type->value);
    }

    public function publicPublishedCount(): int
    {
        return (int) (round($this->newQuery()->published()->public()->count() / 25) * 25);
    }

    public function explorableCount(): int
    {
        return $this->newQuery()->public()->published()->count();
    }

    public function public(): static
    {
        return $this->where('is_public', true);
    }

    public function drafts(): static
    {
        return $this->where('is_published', false);
    }

    public function published(): static
    {
        return $this->where('is_published', true);
    }
}
