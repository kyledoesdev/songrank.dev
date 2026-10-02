<?php

namespace App\Livewire\Reviews\Concerns;

use App\Enums\ReviewType;
use App\Models\Review;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

trait HasReviewSubject
{
    use HasReviewFlashErrors;

    public string $searchTerm = '';

    public ?array $searchResults = null;

    public ?array $subject = null;

    abstract public function reviewType(): ReviewType;

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function results(): Collection
    {
        return collect($this->searchResults);
    }

    public function hasSubject(): bool
    {
        return filled($this->subject);
    }

    public function subjectName(): ?string
    {
        return $this->subject['name'] ?? null;
    }

    public function spotifyUrlFor(array $entry): string
    {
        return "https://open.spotify.com/{$this->reviewType()->value}/{$entry['id']}";
    }

    public function chooseSubject(string $id): void
    {
        $chosen = $this->results()->firstWhere('id', $id);

        if (is_null($chosen)) {
            return;
        }

        if ($this->hasReviewedAlready($chosen['id'])) {
            $this->alreadyReviewed($chosen['name']);

            return;
        }

        $this->subject = $chosen;
        $this->searchResults = null;
        $this->searchTerm = '';
    }

    public function clearSubject(): void
    {
        $this->subject = null;
    }

    /**
     * Enforced here rather than by a unique index: reviews soft delete, and an
     * index spanning deleted_at would never fire for live rows.
     */
    protected function hasReviewedAlready(string $spotifyId): bool
    {
        $type = $this->reviewType();
        $model = $type->subjectModel();

        $subjectId = $model::query()
            ->where($type->spotifyIdColumn(), $spotifyId)
            ->value('id');

        if (is_null($subjectId)) {
            return false;
        }

        return Review::query()
            ->where('user_id', Auth::id())
            ->where('subject_type', (new $model)->getMorphClass())
            ->where('subject_id', $subjectId)
            ->exists();
    }
}
