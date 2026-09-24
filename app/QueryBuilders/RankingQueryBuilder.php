<?php

namespace App\QueryBuilders;

use App\Enums\RankingType;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Show;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RankingQueryBuilder extends Builder
{
    public function forExplorePage(?string $search = null): static
    {
        return $this->newQuery()
            ->public()
            ->completed()
            ->when($search, fn (Builder $query, string $search) => $query->whereSourceNameLike($search))
            ->withHasPodcastEpisode()
            ->with('user', 'source')
            ->with('songs', fn ($query) => $query->where('rank', 1))
            ->withCount('songs')
            ->orderBy('completed_at', 'desc');
    }

    public function forProfilePage(User $user): static
    {
        return $this->newQuery()
            ->where('user_id', $user ? $user->getKey() : Auth::id())
            ->when($user && $user->getKey() !== Auth::id(), fn (Builder $query) => $query->public()->completed())
            ->withHasPodcastEpisode()
            ->with('user', 'source')
            ->with('songs', fn ($query) => $query->with('artist'))
            ->withCount('songs')
            ->orderByRaw('completed_at IS NULL DESC, completed_at DESC');
    }

    public function forDashboard(User $user): static
    {
        return $this->newQuery()
            ->where('user_id', $user->getKey())
            ->with('user', 'source')
            ->with('songs', fn ($query) => $query->where('rank', 1))
            ->withCount('songs')
            ->orderByRaw('completed_at IS NULL DESC, completed_at DESC');
    }

    public function forNewsletter(): static
    {
        return $this->newQuery()
            ->public()
            ->completed()
            ->whereBetween('completed_at', [now()->subMonth(), now()])
            ->withHasPodcastEpisode()
            ->with('user', 'source')
            ->with('songs', fn ($query) => $query->where('rank', 1))
            ->withCount('songs')
            ->orderBy('completed_at', 'desc');
    }

    public function mostSongs(int $limit = 10): static
    {
        return $this->newQuery()
            ->public()
            ->completed()
            ->has('songs', '<=', 500)
            ->withCount('songs')
            ->with('user', 'source')
            ->orderByDesc('songs_count')
            ->orderBy('name')
            ->limit($limit);
    }

    public function publicRankedCount(): int
    {
        return (int) (round($this->newQuery()->completed()->public()->count() / 25) * 25);
    }

    public function explorableCount(): int
    {
        return $this->newQuery()->public()->completed()->count();
    }

    public function whereSourceNameLike(string $search): static
    {
        return $this->whereHasMorph('source', [Artist::class, Playlist::class, Show::class],
            fn (Builder $query, string $type) => $query->where(
                $type === Artist::class ? 'artist_name' : 'name',
                'LIKE',
                "%{$search}%",
            ),
        );
    }

    public function withHasPodcastEpisode()
    {
        return $this->addSelect([
            'has_podcast_episode' => function ($q) {
                $q->selectRaw('CASE WHEN rankings.type = ? OR EXISTS (
                    SELECT 1 FROM songs
                    INNER JOIN artists ON songs.artist_id = artists.id
                    WHERE songs.ranking_id = rankings.id
                    AND artists.is_podcast = 1
                ) THEN 1 ELSE 0 END', [RankingType::SHOW->value]);
            },
        ]);
    }

    public function public(): static
    {
        return $this->where('is_public', true);
    }

    public function inProgress(): static
    {
        return $this->where('is_ranked', false);
    }

    public function completed(): static
    {
        return $this->where('is_ranked', true);
    }
}
