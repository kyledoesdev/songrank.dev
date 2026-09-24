<?php

namespace App\Livewire\SongRank\Concerns;

use App\Livewire\Concerns\InteractsWithAlerts;

trait HasSetupFlashErrors
{
    use InteractsWithAlerts;

    protected function invalidSearchTerm(): void
    {
        $this->flash(
            title: 'Please enter a valid search term.',
            icon: 'error',
        );
    }

    protected function noArtistFound(string $searchTerm): void
    {
        $this->flash(
            title: 'No Artists found.',
            message: "Could not find artists for search term: {$searchTerm}",
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
            message: "Playlists can have a max of 500 songs. Something went wrong trying to find this playlist: {$searchTerm}.",
            icon: 'error',
        );
    }

    protected function invalidShowUrl(): void
    {
        $this->flash(
            title: 'Invalid URL.',
            message: 'Please enter a valid Spotify show URL (e.g. https://open.spotify.com/show/...).',
            icon: 'error',
        );
    }

    protected function showNotFound(string $searchTerm): void
    {
        $this->flash(
            title: 'Could not find show.',
            message: "Shows can have a max of 500 episodes. Something went wrong trying to find this show: {$searchTerm}.",
            icon: 'error',
        );
    }

    protected function acknowledgeDeletedTracks(string $names): void
    {
        $this->flash(
            title: 'Some tracks could not be found',
            message: 'The following tracks were removed from Spotify and could not be added to this ranking. You can proceed with the other tracks. Sorry 🤷',
            icon: 'error',
            submessage: $names,
        );
    }

    protected function notEnoughTracks(string $name): void
    {
        $this->flash(
            title: 'Not enough tracks to rank.',
            message: "The artist: {$name} does not have enough tracks to rank.",
            icon: 'error',
        );
    }

    protected function featuredTracksUnavailable(string $name): void
    {
        $this->flash(
            title: 'Could not load featured tracks.',
            message: "Something went wrong loading the tracks {$name} is featured on. Please try again.",
            icon: 'error',
        );
    }

    protected function tooManyTracks(int $count, int $max): void
    {
        $remove = $count - $max;

        $this->flash(
            title: 'Too many tracks to rank.',
            message: "Rankings can have a max of {$max} tracks. You have {$count} selected, please remove {$remove} more.",
            icon: 'error',
        );
    }

    protected function flashRankingLimitReached(int $limit): void
    {
        $this->flash(
            title: "You have reached {$limit} rankings.",
            message: "Free accounts can keep up to {$limit} rankings. Delete one you are finished with, or go Pro for unlimited rankings.",
            icon: 'error',
        );
    }
}
