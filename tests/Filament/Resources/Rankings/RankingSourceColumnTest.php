<?php

use App\Filament\Resources\Rankings\Pages\ListRankings;
use App\Models\Artist;
use App\Models\Playlist;
use App\Models\Show;
use Livewire\Livewire;

describe('source column', function () {
    test('renders the source name for every ranking type', function () {
        $artistRanking = publicCompletedRanking(Artist::factory()->createOne(['artist_name' => 'Column Artist']));
        $playlistRanking = publicCompletedRanking(Playlist::factory()->createOne(['name' => 'Column Playlist']));
        $showRanking = publicCompletedRanking(Show::factory()->createOne(['name' => 'Column Show']));

        Livewire::actingAs(kyle())
            ->test(ListRankings::class)
            ->assertCanSeeTableRecords([$artistRanking, $playlistRanking, $showRanking])
            ->assertTableColumnStateSet('source', 'Column Artist', $artistRanking)
            ->assertTableColumnStateSet('source', 'Column Playlist', $playlistRanking)
            ->assertTableColumnStateSet('source', 'Column Show', $showRanking);
    });

    test('searching by artist name finds only the artist ranking', function () {
        assertSourceSearchFinds('Needle Artist', Artist::factory()->createOne(['artist_name' => 'Needle Artist']));
    });

    test('searching by playlist name finds only the playlist ranking', function () {
        assertSourceSearchFinds('Needle Playlist', Playlist::factory()->createOne(['name' => 'Needle Playlist']));
    });

    test('searching by show name finds only the show ranking', function () {
        assertSourceSearchFinds('Needle Show', Show::factory()->createOne(['name' => 'Needle Show']));
    });
});

function assertSourceSearchFinds(string $search, $source): void
{
    $match = publicCompletedRanking($source);
    $other = publicCompletedRanking(Artist::factory()->createOne(['artist_name' => 'Unrelated Artist']));

    Livewire::actingAs(kyle())
        ->test(ListRankings::class)
        ->searchTable($search)
        ->assertCanSeeTableRecords([$match])
        ->assertCanNotSeeTableRecords([$other]);
}
