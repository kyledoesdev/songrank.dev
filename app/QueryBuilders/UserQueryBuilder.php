<?php

namespace App\QueryBuilders;

use Illuminate\Database\Eloquent\Builder;

class UserQueryBuilder extends Builder
{
    public function topCreators(int $limit = 10): static
    {
        return $this->newQuery()
            ->whereHas('rankings', fn (Builder $query) => $query->public()->completed())
            ->withCount(['rankings' => fn (Builder $query) => $query->public()->completed()])
            ->orderByDesc('rankings_count')
            ->orderBy('name')
            ->limit($limit);
    }

    public function forGrantPicker(string $search): static
    {
        return $this->whereAny(['name', 'email', 'spotify_id'], 'like', "%{$search}%")
            ->orderBy('name')
            ->limit(25);
    }

    public function wherePreviouslyDeleted(string $spotifyId): static
    {
        return $this->newQuery()
            ->withTrashed()
            ->where('spotify_id', $spotifyId)
            ->whereNotNull('deleted_at');
    }

    public function whereEmailBelongsToAnotherUser(string $email, int $userId): static
    {
        return $this->newQuery()
            ->withTrashed()
            ->where('email', $email)
            ->whereKeyNot($userId);
    }

    public function roundedUserCount(): int
    {
        return (int) (round($this->newQuery()->count() / 50) * 50);
    }
}
