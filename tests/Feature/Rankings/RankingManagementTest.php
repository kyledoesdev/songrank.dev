<?php

use App\Livewire\Ranking\Card as ProfileRankingCard;
use App\Livewire\Ranking\EditRanking;
use App\Models\Artist;
use App\Models\Ranking;
use App\Models\Song;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Artist::factory()->create();
    User::factory()->createOne();
});

describe('editing rankings', function () {
    test('owner can view the edit page', function () {
        $user = User::factory()
            ->has(Ranking::factory()->count(1))
            ->createOne();

        actingAs($user)
            ->get(route('rank.edit', ['id' => $user->rankings->first()->getKey()]))
            ->assertOk();
    });

    test('non-owner cannot view the edit page', function () {
        $ranking = Ranking::factory()->create();

        actingAs(User::factory()->createOne())
            ->get(route('rank.edit', ['id' => $ranking->getKey()]))
            ->assertNotFound();
    });

    test('owner can update name and visibility', function () {
        $user = User::factory()->createOne();

        $ranking = Ranking::factory()->create([
            'user_id' => $user->getKey(),
            'is_public' => false,
        ]);

        Livewire::actingAs($user)
            ->test(EditRanking::class, ['id' => $ranking->getKey()])
            ->set('form.name', 'new name')
            ->set('form.is_public', true)
            ->call('update')
            ->assertHasNoErrors();

        $ranking->refresh();

        expect($ranking->name)->toBe('new name');
        expect($ranking->is_public)->toBeTrue();
    });

    test('non-owner cannot view the edit page of a public ranking', function () {
        $ranking = publicCompletedRanking();

        actingAs(User::factory()->createOne())
            ->get(route('rank.edit', ['id' => $ranking->getKey()]))
            ->assertNotFound();
    });

    test('non-owner cannot update name and visibility', function () {
        $user = User::factory()->createOne();

        $ranking = Ranking::factory()->create(['is_public' => false]);

        Livewire::actingAs($user)
            ->test(EditRanking::class, ['id' => $ranking->getKey()])
            ->assertNotFound();
    });
});

describe('deleting rankings', function () {
    test('owner can delete their ranking', function () {
        $user = User::factory()
            ->has(Ranking::factory())
            ->createOne();

        $ranking = $user->rankings->first();

        expect($ranking->deleted_at)->toBeNull();
        expect(Song::where('ranking_id', $ranking->getKey())->count())->toBe(10);

        Livewire::actingAs($user)
            ->test(ProfileRankingCard::class, ['ranking' => $ranking])
            ->call('destroy')
            ->assertDispatched('rankings-updated');

        $ranking->refresh();

        expect($ranking->deleted_at)->not->toBeNull();
        expect(Song::where('ranking_id', $ranking->getKey())->count())->toBe(0);
    });

    test('owner can delete their ranking from the edit page', function () {
        $user = User::factory()
            ->has(Ranking::factory())
            ->createOne();

        $ranking = $user->rankings->first();

        Livewire::actingAs($user)
            ->test(EditRanking::class, ['id' => $ranking->getKey()])
            ->call('destroy')
            ->assertRedirect(route('profile', ['id' => $user->spotify_id]))
            ->assertSessionHas('success', 'Ranking removed successfully.');

        $ranking->refresh();

        expect($ranking->deleted_at)->not->toBeNull();
        expect(Song::where('ranking_id', $ranking->getKey())->count())->toBe(0);
    });

    test('non-owner cannot delete another user\'s ranking', function () {
        $owner = User::factory()
            ->has(Ranking::factory())
            ->createOne();

        $nonOwner = User::factory()->createOne();
        $ranking = $owner->rankings->first();

        Livewire::actingAs($nonOwner)
            ->test(ProfileRankingCard::class, ['ranking' => $ranking])
            ->call('destroy')
            ->assertForbidden();

        $ranking->refresh();

        expect($ranking->deleted_at)->toBeNull();
        expect(Song::where('ranking_id', $ranking->getKey())->count())->toBe(10);
    });
});
