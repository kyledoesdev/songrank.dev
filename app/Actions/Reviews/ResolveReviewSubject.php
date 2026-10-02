<?php

namespace App\Actions\Reviews;

use App\Actions\Artists\ResolveArtists;
use App\Actions\Tierlists\ResolveTierlistEntries;
use App\Enums\ReviewType;
use App\Models\Album;
use App\Models\Artist;
use App\Models\Track;
use Illuminate\Database\Eloquent\Model;

/**
 * @see ResolveTierlistEntries for the many-entry version
 */
final class ResolveReviewSubject
{
    public function handle(ReviewType $type, array $entry): Model
    {
        return match ($type) {
            ReviewType::ARTIST => $this->artist($entry),
            ReviewType::ALBUM => $this->album($entry),
            ReviewType::TRACK => $this->track($entry),
        };
    }

    private function artist(array $entry): Artist
    {
        $artist = Artist::firstOrCreate(['artist_id' => $entry['id']], [
            'artist_name' => $entry['name'],
            'artist_img' => $entry['cover'] ?? null,
        ]);

        /* An artist first met as a credit on somebody else's album has no
           picture, and the review card is mostly picture. */
        if (blank($artist->artist_img) && filled($entry['cover'] ?? null)) {
            $artist->update(['artist_img' => $entry['cover']]);
        }

        return $artist;
    }

    private function album(array $entry): Album
    {
        return Album::firstOrCreate(['album_id' => $entry['id']], [
            'artist_id' => $this->creditedArtist($entry),
            'name' => $entry['name'],
            'cover' => $entry['cover'] ?? null,
            'release_date' => $entry['release_date'] ?? null,
            'total_tracks' => $entry['total_tracks'] ?? null,
        ]);
    }

    private function track(array $entry): Track
    {
        return Track::firstOrCreate(['track_id' => $entry['id']], [
            'artist_id' => $this->creditedArtist($entry),
            'name' => $entry['name'],
            'cover' => $entry['cover'] ?? null,
            'album_name' => $entry['album_name'] ?? null,
        ]);
    }

    private function creditedArtist(array $entry): ?int
    {
        return (new ResolveArtists)->handle(collect([[
            'id' => $entry['artist_id'] ?? null,
            'name' => $entry['artist_name'] ?? null,
        ]]))->first();
    }
}
