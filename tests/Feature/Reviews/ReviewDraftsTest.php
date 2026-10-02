<?php

use App\Livewire\Dashboard\InProgress;
use App\Models\Review;
use App\Models\User;
use Livewire\Livewire;

describe('getting back to a draft', function () {
    it('lists drafts under pick up where you left off, linking to the editor', function () {
        $user = kyle();
        $review = Review::factory()->for($user)->createOne(['name' => 'Half Written']);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertSee('Half Written')
            ->assertSee(route('review.edit', ['id' => $review->getKey()]), escape: false);
    });

    it('keeps published reviews out of it', function () {
        $user = kyle();
        Review::factory()->for($user)->published()->createOne(['name' => 'All Done']);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertDontSee('All Done');
    });

    it('leaves other people drafts out of it', function () {
        Review::factory()->createOne(['name' => 'Somebody Elses']);

        Livewire::actingAs(kyle())
            ->test(InProgress::class)
            ->assertDontSee('Somebody Elses');
    });

    it('leaves drafts out while the feature is off', function () {
        $user = User::factory()->createOne();
        Review::factory()->for($user)->createOne(['name' => 'Half Written']);

        Livewire::actingAs($user)
            ->test(InProgress::class)
            ->assertDontSee('Half Written');
    });
});
