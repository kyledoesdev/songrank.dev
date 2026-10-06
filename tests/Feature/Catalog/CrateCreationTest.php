<?php

use App\Actions\Catalog\StoreCrate;
use App\Models\Crate;
use App\Models\User;

describe('creating a crate', function () {
    it('starts a crate at the top level', function () {
        $user = User::factory()->createOne();

        $crate = (new StoreCrate)->handle($user, ['name' => 'Foster the People']);

        expect($crate->user_id)->toBe($user->getKey())
            ->and($crate->name)->toBe('Foster the People')
            ->and($crate->parent_id)->toBeNull()
            ->and($crate->depth)->toBe(0)
            ->and($crate->iconClass())->toBe('fa-folder')
            ->and($crate->fresh()->color)->toBe('#A855F7');
    });

    it('nests a crate one level down for a Pro account', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();

        $crate = (new StoreCrate)->handle($user, ['name' => 'Sacred Hearts Club'], $parent);

        expect($crate->parent_id)->toBe($parent->getKey())
            ->and($crate->depth)->toBe(1)
            ->and($parent->children->modelKeys())->toBe([$crate->getKey()]);
    });

    it('trims a name to what the column holds', function () {
        $crate = (new StoreCrate)->handle(User::factory()->createOne(), ['name' => str_repeat('a', 60)]);

        expect($crate->name)->toHaveLength(40);
    });
});

describe('where a new crate lands', function () {
    it('joins the end of its own list', function () {
        $user = proUser();
        $parent = Crate::factory()->for($user)->create();

        $first = (new StoreCrate)->handle($user, ['name' => 'Torches']);
        $nested = (new StoreCrate)->handle($user, ['name' => 'Supermodel'], $parent);
        $second = (new StoreCrate)->handle($user, ['name' => 'Paradise State of Mind']);

        expect($parent->position)->toBe(0)
            ->and($first->position)->toBe(1)
            ->and($second->position)->toBe(2)
            ->and($nested->position)->toBe(0);
    });

    it('counts only that user\'s crates', function () {
        Crate::factory()->count(3)->create();

        $crate = (new StoreCrate)->handle(User::factory()->createOne(), ['name' => 'Torches']);

        expect($crate->position)->toBe(0);
    });
});

describe('the breadcrumb', function () {
    it('lists every crate above, outermost first', function () {
        $user = User::factory()->createOne();
        $top = Crate::factory()->for($user)->create();
        $middle = Crate::factory()->nestedIn($top)->create();
        $bottom = Crate::factory()->nestedIn($middle)->create();

        expect($bottom->ancestors()->pluck('id')->all())->toBe([$top->getKey(), $middle->getKey()])
            ->and($top->ancestors())->toBeEmpty();
    });
});
