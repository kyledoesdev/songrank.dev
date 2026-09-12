<?php

use App\Actions\Artists\ResolveArtists;
use App\Models\Artist;

describe('resolving credited artists', function () {
    it('creates an artist it has never seen and hands back its row id', function () {
        $resolved = resolveCredits([['id' => 'new-id', 'name' => 'Portishead']]);

        $artist = Artist::where('artist_id', 'new-id')->firstOrFail();

        expect($resolved->get('new-id'))->toBe($artist->getKey())
            ->and($artist->artist_name)->toBe('Portishead');
    });

    it('leaves an artist we already know exactly as it is', function () {
        $existing = Artist::factory()->create([
            'artist_id' => 'known-id',
            'artist_name' => 'Portishead',
            'artist_img' => 'https://example.test/portishead.png',
        ]);

        $resolved = resolveCredits([['id' => 'known-id', 'name' => 'Portishead (Remastered)']]);

        expect(Artist::count())->toBe(1)
            ->and($resolved->get('known-id'))->toBe($existing->getKey())
            ->and($existing->fresh()->artist_name)->toBe('Portishead')
            ->and($existing->fresh()->artist_img)->toBe('https://example.test/portishead.png');
    });

    it('resolves known and unknown artists in one pass', function () {
        $existing = Artist::factory()->create(['artist_id' => 'known-id']);

        $resolved = resolveCredits([
            ['id' => 'known-id', 'name' => 'Known'],
            ['id' => 'new-id', 'name' => 'New'],
        ]);

        expect($resolved)->toHaveCount(2)
            ->and($resolved->get('known-id'))->toBe($existing->getKey())
            ->and($resolved->get('new-id'))->toBe(Artist::where('artist_id', 'new-id')->first()->getKey());
    });

    it('collapses the same artist credited several times', function () {
        resolveCredits([
            ['id' => 'same-id', 'name' => 'Portishead'],
            ['id' => 'same-id', 'name' => 'Portishead'],
        ]);

        expect(Artist::where('artist_id', 'same-id')->count())->toBe(1);
    });

    it('falls back to a placeholder rather than failing on a nameless credit', function () {
        resolveCredits([['id' => 'nameless-id', 'name' => null]]);

        expect(Artist::where('artist_id', 'nameless-id')->firstOrFail()->artist_name)
            ->toBe('Unknown Artist');
    });

    it('skips a credit with no spotify id at all', function () {
        expect(resolveCredits([['id' => null, 'name' => 'Nobody']]))->toBeEmpty()
            ->and(Artist::count())->toBe(0);
    });

    it('resolves nothing from an empty set', function () {
        expect(resolveCredits([]))->toBeEmpty();
    });
});

function resolveCredits(array $artists)
{
    return (new ResolveArtists)->handle(collect($artists));
}
