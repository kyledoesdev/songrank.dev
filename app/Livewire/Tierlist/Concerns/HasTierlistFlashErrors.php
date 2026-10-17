<?php

namespace App\Livewire\Tierlist\Concerns;

use App\Enums\TierlistType;
use App\Livewire\Concerns\InteractsWithAlerts;

trait HasTierlistFlashErrors
{
    use InteractsWithAlerts;

    protected function invalidSearchTerm(): void
    {
        $this->flash(
            title: 'Please enter a valid search term.',
            icon: 'error',
        );
    }

    protected function nothingFound(TierlistType $type, string $searchTerm): void
    {
        $this->flash(
            title: 'Nothing found.',
            message: "Could not find any {$type->itemLabel()} for: {$searchTerm}",
            icon: 'error',
        );
    }

    protected function discographyUnavailable(string $name): void
    {
        $this->flash(
            title: 'Could not load that discography.',
            message: "Something went wrong loading releases for {$name}. Please try again.",
            icon: 'error',
        );
    }

    protected function invalidPlaylistUrl(): void
    {
        $this->flash(
            title: 'No playlist found.',
            message: 'We could not find a playlist for that URL. Please ensure you entered a valid spotify playlist URL and the playlist is public.',
            icon: 'error',
        );
    }

    protected function playlistNotFound(string $searchTerm): void
    {
        $this->flash(
            title: 'Could not find playlist.',
            message: "Playlists can have a max of 500 tracks. Something went wrong trying to find this playlist: {$searchTerm}.",
            icon: 'error',
        );
    }

    protected function flashBankIsFull(int $limit): void
    {
        $this->flash(
            title: "That is all {$limit} spots filled.",
            message: "A tier list can hold {$limit} entries on your account. Take one out to make room, or go Pro for a bigger board.",
            icon: 'error',
        );
    }

    protected function roomForOnlySome(int $added, int $skipped): void
    {
        $this->flash(
            title: "Added {$added}, left {$skipped} behind.",
            message: 'Your board filled up partway through the import. Go Pro if you want the rest of them on there.',
            icon: 'error',
        );
    }

    protected function nothingToRank(): void
    {
        $this->flash(
            title: 'Nothing on the board yet.',
            message: 'Add at least two entries before you start sorting them into tiers.',
            icon: 'error',
        );
    }

    protected function flashTierlistLimitReached(TierlistType $type, int $limit): void
    {
        $this->flash(
            title: "You have used your {$type->label()} tier list.",
            message: "Free accounts can keep {$limit} {$type->label()} tier list. Delete the one you have, or go Pro for unlimited lists of every type.",
            icon: 'error',
        );
    }
}
