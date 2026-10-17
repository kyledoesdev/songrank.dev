<?php

use App\Livewire\SongRank\SongRankProcess;
use App\Models\Artist;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    Artist::factory()->create();
    User::factory()->createOne();
});

describe('undo last choice', function () {
    test('undo reverts to the previous comparison pair', function () {
        $user = User::factory()->createOne();
        $ranking = algorithmRanking($user, 'Undo Test', expectedSongTitles(5));

        $sortingState = sortingStateInOrder($ranking);

        $component = Livewire::actingAs($user)
            ->test(SongRankProcess::class, [
                'ranking' => $ranking,
                'sortingState' => $sortingState,
            ]);

        $firstLeft = $component->get('currentSong1');
        $firstRight = $component->get('currentSong2');

        $component->call('chooseSong', $firstLeft['id']);

        $component->assertSet('canUndo', true);

        $sortingState->refresh();
        $comparisonsAfterChoice = $sortingState->completed_comparisons;

        $component->call('undoLastChoice');

        $sortingState->refresh();

        expect($sortingState->completed_comparisons)->toBe($comparisonsAfterChoice - 1);
        expect($component->get('currentSong1')['id'])->toBe($firstLeft['id']);
        expect($component->get('currentSong2')['id'])->toBe($firstRight['id']);
    });

    test('undo is disabled before any choices are made', function () {
        $user = User::factory()->createOne();
        $ranking = algorithmRanking($user, 'No Undo Test', expectedSongTitles(5));

        $sortingState = sortingStateInOrder($ranking);

        $component = Livewire::actingAs($user)
            ->test(SongRankProcess::class, [
                'ranking' => $ranking,
                'sortingState' => $sortingState,
            ]);

        $component->assertSet('canUndo', false);

        $songBefore = $component->get('currentSong1');

        $component->call('undoLastChoice');

        expect($component->get('currentSong1')['id'])->toBe($songBefore['id']);
    });

    test('undo disables after all history is exhausted', function () {
        $user = User::factory()->createOne();
        $ranking = algorithmRanking($user, 'Exhaust Undo Test', expectedSongTitles(5));

        $sortingState = sortingStateInOrder($ranking);

        $component = Livewire::actingAs($user)
            ->test(SongRankProcess::class, [
                'ranking' => $ranking,
                'sortingState' => $sortingState,
            ]);

        simulateRankingComparisons($component, maxComparisons: 3);

        $sortingState->refresh();
        expect($sortingState->completed_comparisons)->toBe(3);

        $component->call('undoLastChoice');
        $component->call('undoLastChoice');
        $component->call('undoLastChoice');

        $component->assertSet('canUndo', false);

        $sortingState->refresh();
        expect($sortingState->completed_comparisons)->toBe(0);
    });

    test('ranking still completes correctly after undoing and re-choosing', function () {
        $user = User::factory()->createOne();
        $expectedSongTitles = expectedSongTitles(5);
        $ranking = algorithmRanking($user, 'Undo Then Complete', $expectedSongTitles);

        $sortingState = sortingStateInOrder($ranking);

        $component = Livewire::actingAs($user)
            ->test(SongRankProcess::class, [
                'ranking' => $ranking,
                'sortingState' => $sortingState,
            ]);

        simulateRankingComparisons($component, maxComparisons: 3);

        $component->call('undoLastChoice');
        $component->call('undoLastChoice');

        simulateRankingComparisons($component, maxComparisons: 50);

        $ranking->refresh();

        expect($ranking->is_ranked)->toBeTrue();

        foreach ($ranking->songs()->get() as $song) {
            expect($song->title)->toBe($expectedSongTitles[$song->rank]);
        }
    });

    test('decision history respects the undo cap', function () {
        $user = User::factory()->createOne();
        $ranking = algorithmRanking($user, 'Undo Cap Test', expectedSongTitles(20));

        $sortingState = sortingStateInOrder($ranking);

        $component = Livewire::actingAs($user)
            ->test(SongRankProcess::class, [
                'ranking' => $ranking,
                'sortingState' => $sortingState,
            ]);

        simulateRankingComparisons($component, maxComparisons: SongRankProcess::MAX_UNDO_HISTORY + 5);

        $sortingState->refresh();

        expect($sortingState->sorting_state['decision_history'])->toHaveCount(SongRankProcess::MAX_UNDO_HISTORY);
    });

    test('undo persists across sessions', function () {
        $user = User::factory()->createOne();
        $ranking = algorithmRanking($user, 'Undo Persist Test', expectedSongTitles(5));

        $sortingState = sortingStateInOrder($ranking);

        $firstSession = Livewire::actingAs($user)
            ->test(SongRankProcess::class, [
                'ranking' => $ranking,
                'sortingState' => $sortingState,
            ]);

        simulateRankingComparisons($firstSession, maxComparisons: 3);

        $secondSession = Livewire::actingAs($user)
            ->test(SongRankProcess::class, [
                'ranking' => $ranking->fresh(),
                'sortingState' => $sortingState->fresh(),
            ]);

        $secondSession->assertSet('canUndo', true);

        $secondSession->call('undoLastChoice');

        $sortingState->refresh();
        expect($sortingState->completed_comparisons)->toBe(2);
    });
});
