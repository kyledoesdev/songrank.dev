<?php

use App\Actions\Artists\ResolveArtists;
use App\Models\Artist;

describe('resolving credited artists', function () {
    it('creates an artist it has never seen and hands back its row id', function () {
        $resolved = resolveCredits([['id' => 'local-natives-id', 'name' => 'Local Natives']]);

        $artist = Artist::where('artist_id', 'local-natives-id')->firstOrFail();

        expect($resolved->get('local-natives-id'))->toBe($artist->getKey())
            ->and($artist->artist_name)->toBe('Local Natives');
    });

    it('leaves an artist we already know exactly as it is', function () {
        $existing = Artist::factory()->create([
            'artist_id' => 'tame-impala-id',
            'artist_name' => 'Tame Impala',
            'artist_img' => 'https://example.test/tame-impala.png',
        ]);

        $resolved = resolveCredits([['id' => 'tame-impala-id', 'name' => 'Tame Impala (Remastered)']]);

        expect(Artist::count())->toBe(1)
            ->and($resolved->get('tame-impala-id'))->toBe($existing->getKey())
            ->and($existing->fresh()->artist_name)->toBe('Tame Impala')
            ->and($existing->fresh()->artist_img)->toBe('https://example.test/tame-impala.png');
    });

    it('resolves known and unknown artists in one pass', function () {
        $existing = Artist::factory()->create(['artist_id' => 'tame-impala-id']);

        $resolved = resolveCredits([
            ['id' => 'tame-impala-id', 'name' => 'Tame Impala'],
            ['id' => 'foster-the-people-id', 'name' => 'Foster the People'],
        ]);

        expect($resolved)->toHaveCount(2)
            ->and($resolved->get('tame-impala-id'))->toBe($existing->getKey())
            ->and($resolved->get('foster-the-people-id'))
            ->toBe(Artist::where('artist_id', 'foster-the-people-id')->first()->getKey());
    });

    it('collapses the same artist credited several times', function () {
        resolveCredits([
            ['id' => 'foster-the-people-id', 'name' => 'Foster the People'],
            ['id' => 'foster-the-people-id', 'name' => 'Foster the People'],
        ]);

        expect(Artist::where('artist_id', 'foster-the-people-id')->count())->toBe(1);
    });

    it('falls back to a placeholder rather than failing on a nameless credit', function () {
        resolveCredits([['id' => 'uncredited-id', 'name' => null]]);

        expect(Artist::where('artist_id', 'uncredited-id')->firstOrFail()->artist_name)
            ->toBe('Unknown Artist');
    });

    it('skips a credit with no spotify id at all', function () {
        expect(resolveCredits([['id' => null, 'name' => 'Nobody']]))->toBeEmpty()
            ->and(Artist::count())->toBe(0);
    });
});

function resolveCredits(array $artists)
{
    return (new ResolveArtists)->handle(collect($artists));
}
