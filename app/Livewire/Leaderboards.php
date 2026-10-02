<?php

namespace App\Livewire;

use App\Models\Album;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Component;

class Leaderboards extends Component
{
    public function render()
    {
        return view('livewire.leaderboards', [
            'topArtists' => $this->topArtists(),
            'topCreators' => $this->topCreators(),
            'biggestRankings' => $this->biggestRankings(),
            'topReviewedArtists' => $this->topReviewedArtists(),
            'topReviewedAlbums' => $this->topReviewedAlbums(),
            'topReviewedTracks' => $this->topReviewedTracks(),
        ]);
    }

    private function topArtists(): Collection
    {
        return cache()->remember('leaderboards:top-artists', now()->addHour(), fn () => Artist::query()
            ->topArtists()
            ->get()
            ->map(fn (Artist $artist) => [
                'image' => $artist->artist_img,
                'name' => $artist->artist_name,
                'count' => $artist->artist_rankings_count,
                'url' => null,
                'spotify_url' => $artist->spotifyUrl(),
            ]));
    }

    private function topCreators(): Collection
    {
        return cache()->remember('leaderboards:top-creators', now()->addHour(), fn () => User::query()
            ->topCreators()
            ->get()
            ->map(fn (User $user) => [
                'image' => $user->avatar,
                'name' => $user->name,
                'count' => $user->rankings_count,
                'url' => route('profile', ['id' => $user->spotify_id]),
                'spotify_url' => null,
            ]));
    }

    private function biggestRankings(): Collection
    {
        return cache()->remember('leaderboards:most-songs', now()->addHour(), fn () => Ranking::query()
            ->mostSongs()
            ->get()
            ->map(fn (Ranking $ranking) => [
                'image' => $ranking->source->cover(),
                'name' => $ranking->name,
                'subtitle' => $ranking->user->name,
                'count' => $ranking->songs_count,
                'url' => route('ranking', ['id' => $ranking->getKey()]),
                'spotify_url' => $ranking->source->spotifyUrl(),
            ]));
    }

    private function topReviewedArtists(): Collection
    {
        return cache()->remember('leaderboards:top-reviewed-artists', now()->addHour(), fn () => Artist::query()
            ->topReviewed()
            ->get()
            ->map(fn (Artist $artist) => [
                'image' => $artist->cover(),
                'name' => $artist->name(),
                'count' => $artist->reviews_count,
                'url' => null,
                'spotify_url' => $artist->spotifyUrl(),
            ]));
    }

    private function topReviewedAlbums(): Collection
    {
        return cache()->remember('leaderboards:top-reviewed-albums', now()->addHour(), fn () => Album::query()
            ->topReviewed()
            ->with('artist')
            ->get()
            ->map(fn (Album $album) => [
                'image' => $album->cover(),
                'name' => $album->name(),
                'subtitle' => $album->artist?->name(),
                'count' => $album->reviews_count,
                'url' => null,
                'spotify_url' => $album->spotifyUrl(),
            ]));
    }

    private function topReviewedTracks(): Collection
    {
        return cache()->remember('leaderboards:top-reviewed-tracks', now()->addHour(), fn () => Track::query()
            ->topReviewed()
            ->with('artist')
            ->get()
            ->map(fn (Track $track) => [
                'image' => $track->cover(),
                'name' => $track->name(),
                'subtitle' => $track->artist?->name(),
                'count' => $track->reviews_count,
                'url' => null,
                'spotify_url' => $track->spotifyUrl(),
            ]));
    }
}
