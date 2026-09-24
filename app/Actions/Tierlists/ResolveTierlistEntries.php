<?php

namespace App\Actions\Tierlists;

use App\Actions\Artists\ResolveArtists;
use App\Enums\TierlistType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Track;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class ResolveTierlistEntries
{
    /**
     * Turns the entry arrays a Spotify search hands back into the catalog rows a
     * tier list points at, creating the ones we have never seen.
     *
     * Each branch costs a handful of queries whatever the size of the bank: look
     * up what we already hold, insert only the rest in one go. A row we already
     * have is left alone rather than rewritten, so importing five hundred albums
     * does not mean five hundred updates that change nothing.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<string, Model> spotify id => catalog row
     */
    public function handle(TierlistType $type, Collection $entries): Collection
    {
        $entries = $entries->unique('id')->values();

        if ($entries->isEmpty()) {
            return collect();
        }

        return match ($type) {
            TierlistType::ARTIST => $this->resolveArtistEntries($entries),
            TierlistType::ALBUM => $this->resolveAlbumEntries($entries),
            TierlistType::TRACK => $this->resolveTrackEntries($entries),
        };
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<string, Artist>
     */
    private function resolveArtistEntries(Collection $entries): Collection
    {
        $known = Artist::query()
            ->whereIn('artist_id', $entries->pluck('id'))
            ->get()
            ->keyBy('artist_id');

        $unknown = $entries->reject(fn (array $entry) => $known->has($entry['id']));

        if ($unknown->isNotEmpty()) {
            Artist::insert($unknown->map(fn (array $entry) => [
                'artist_id' => $entry['id'],
                'artist_name' => $entry['name'],
                'artist_img' => $entry['cover'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        }

        /* An artist we first met as a credit on somebody else's album has no
           picture, and a tile without one is a blank square on the board. An
           artist search carries it, so fill the gap the one time it shows up. */
        $blank = $known->filter(fn (Artist $artist) => blank($artist->artist_img));

        foreach ($entries->whereIn('id', $blank->keys()) as $entry) {
            if (filled($entry['cover'] ?? null)) {
                $blank->get($entry['id'])->update(['artist_img' => $entry['cover']]);
            }
        }

        return Artist::query()
            ->whereIn('artist_id', $entries->pluck('id'))
            ->get()
            ->keyBy('artist_id');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<string, Album>
     */
    private function resolveAlbumEntries(Collection $entries): Collection
    {
        $artists = $this->creditedArtists($entries);

        $known = Album::query()
            ->whereIn('album_id', $entries->pluck('id'))
            ->pluck('album_id');

        $unknown = $entries->reject(fn (array $entry) => $known->contains($entry['id']));

        if ($unknown->isNotEmpty()) {
            Album::insert($unknown->map(fn (array $entry) => [
                'album_id' => $entry['id'],
                'artist_id' => $artists->get($entry['artist_id'] ?? ''),
                'name' => $entry['name'],
                'cover' => $entry['cover'] ?? null,
                'release_date' => $entry['release_date'] ?? null,
                'total_tracks' => $entry['total_tracks'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        }

        return Album::query()
            ->whereIn('album_id', $entries->pluck('id'))
            ->get()
            ->keyBy('album_id');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<string, Track>
     */
    private function resolveTrackEntries(Collection $entries): Collection
    {
        $artists = $this->creditedArtists($entries);

        $known = Track::query()
            ->whereIn('track_id', $entries->pluck('id'))
            ->pluck('track_id');

        $unknown = $entries->reject(fn (array $entry) => $known->contains($entry['id']));

        if ($unknown->isNotEmpty()) {
            Track::insert($unknown->map(fn (array $entry) => [
                'track_id' => $entry['id'],
                'artist_id' => $artists->get($entry['artist_id'] ?? ''),
                'name' => $entry['name'],
                'cover' => $entry['cover'] ?? null,
                'album_name' => $entry['album_name'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        }

        return Track::query()
            ->whereIn('track_id', $entries->pluck('id'))
            ->get()
            ->keyBy('track_id');
    }

    /**
     * An album or a track credits an artist, and that row has to exist before
     * the foreign key can point at it.
     *
     * @param  Collection<int, array<string, mixed>>  $entries
     * @return Collection<string, int> spotify artist id => artists.id
     */
    private function creditedArtists(Collection $entries): Collection
    {
        return (new ResolveArtists)->handle(
            $entries->map(fn (array $entry) => [
                'id' => $entry['artist_id'] ?? null,
                'name' => $entry['artist_name'] ?? null,
            ])
        );
    }
}
