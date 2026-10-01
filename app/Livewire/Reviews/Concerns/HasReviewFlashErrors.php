<?php

namespace App\Livewire\Reviews\Concerns;

use App\Enums\ReviewType;
use App\Livewire\Concerns\InteractsWithAlerts;

trait HasReviewFlashErrors
{
    use InteractsWithAlerts;

    protected function invalidSearchTerm(): void
    {
        $this->flash(
            title: 'Please enter a valid search term.',
            icon: 'error',
        );
    }

    protected function nothingFound(ReviewType $type, string $searchTerm): void
    {
        $this->flash(
            title: 'Nothing found.',
            message: "Could not find any {$type->subjectLabel()}s for: {$searchTerm}",
            icon: 'error',
        );
    }

    protected function nothingToReview(): void
    {
        $this->flash(
            title: 'Pick something to review first.',
            message: 'Search for an artist, album or track, then choose it from the results.',
            icon: 'error',
        );
    }

    protected function alreadyReviewed(string $name): void
    {
        $this->flash(
            title: "You have already reviewed {$name}.",
            message: 'One review each, so the score means something. Edit or delete the one you have if you have changed your mind.',
            icon: 'error',
        );
    }

    protected function flashReviewLimitReached(int $limit): void
    {
        $this->flash(
            title: 'That is all your reviews used.',
            message: "Free accounts can keep {$limit} reviews. Delete one to make room, or go Pro for as many as you like.",
            icon: 'error',
        );
    }
}
