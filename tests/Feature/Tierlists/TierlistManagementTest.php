<?php

use App\Livewire\Tierlist\EditTierlist;
use App\Models\Tierlist;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

describe('viewing the edit page', function () {
    it('lets the owner view a completed tier list', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        actingAs($owner)
            ->get(route('tierlist.edit', ['id' => $tierlist->getKey()]))
            ->assertOk();
    });

    it('lets the owner view an in-progress tier list', function () {
        $owner = kyle();
        $tierlist = Tierlist::factory()->for($owner)->create();

        actingAs($owner)
            ->get(route('tierlist.edit', ['id' => $tierlist->getKey()]))
            ->assertOk();
    });

    it('hides the page from a non-owner', function () {
        $tierlist = publicCompletedTierlist();

        actingAs(kyle())
            ->get(route('tierlist.edit', ['id' => $tierlist->getKey()]))
            ->assertNotFound();
    });

    it('redirects a guest away', function () {
        $tierlist = publicCompletedTierlist();

        get(route('tierlist.edit', ['id' => $tierlist->getKey()]))
            ->assertRedirect(route('welcome'));
    });
});

describe('updating metadata', function () {
    it('updates name and visibility', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist([
            'user_id' => $owner->getKey(),
            'name' => 'Original Name',
        ]);

        Livewire::actingAs($owner)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->set('form.name', 'New Name')
            ->set('form.is_public', '0')
            ->call('update')
            ->assertHasNoErrors();

        $tierlist->refresh();

        expect($tierlist->name)->toBe('New Name');
        expect($tierlist->is_public)->toBeFalse();
    });

    it('toggles comment settings', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist([
            'user_id' => $owner->getKey(),
            'comments_enabled' => false,
            'comments_replies_enabled' => false,
        ]);

        Livewire::actingAs($owner)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->set('form.comments_enabled', '1')
            ->set('form.comments_replies_enabled', '1')
            ->call('update')
            ->assertHasNoErrors();

        $tierlist->refresh();

        expect($tierlist->comments_enabled)->toBeTrue();
        expect($tierlist->comments_replies_enabled)->toBeTrue();
    });

    it('requires a name', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        Livewire::actingAs($owner)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->set('form.name', '')
            ->call('update')
            ->assertHasErrors(['form.name' => 'required']);
    });

    it('rejects a name longer than thirty characters', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        Livewire::actingAs($owner)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->set('form.name', str_repeat('a', 31))
            ->call('update')
            ->assertHasErrors(['form.name' => 'max']);
    });

    it('hides the edit component from a non-owner', function () {
        $tierlist = publicCompletedTierlist();

        Livewire::actingAs(kyle())
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->assertNotFound();
    });

    it('disables replies when comments are turned off', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist([
            'user_id' => $owner->getKey(),
            'comments_enabled' => true,
            'comments_replies_enabled' => true,
        ]);

        Livewire::actingAs($owner)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->set('form.comments_enabled', '0')
            ->assertSet('form.comments_replies_enabled', '0');
    });
});

describe('deleting tier lists', function () {
    it('soft-deletes the tier list and redirects to the profile', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        expect($tierlist->deleted_at)->toBeNull();

        Livewire::actingAs($owner)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->call('destroy')
            ->assertRedirect(route('profile', ['id' => $owner->spotify_id]))
            ->assertSessionHas('success', 'Tier list removed successfully.');

        $tierlist->refresh();

        expect($tierlist->deleted_at)->not->toBeNull();
    });

    it('hides the delete from a non-owner', function () {
        $tierlist = publicCompletedTierlist();

        Livewire::actingAs(kyle())
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->assertNotFound();
    });
});

describe('board editing gating', function () {
    it('shows the builder to a pro user on a completed list', function () {
        $user = proUser(['is_dev' => true]);
        $tierlist = publicCompletedTierlist(['user_id' => $user->getKey()]);

        Livewire::actingAs($user)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->assertSee('entries are editable')
            ->assertSeeLivewire('tierlist.tierlist-builder');
    });

    it('shows a locked board with upgrade prompt to a free user', function () {
        $owner = kyle();
        $tierlist = publicCompletedTierlist(['user_id' => $owner->getKey()]);

        Livewire::actingAs($owner)
            ->test(EditTierlist::class, ['id' => $tierlist->getKey()])
            ->assertSee('Read-only')
            ->assertSee('Upgrade to Song Rank Pro')
            ->assertDontSeeLivewire('tierlist.tierlist-builder');
    });
});
