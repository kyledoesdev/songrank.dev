<?php

use App\Filament\Resources\Tierlists\Pages\EditTierlist;
use App\Filament\Resources\Tierlists\Pages\ViewTierlist;
use App\Filament\Resources\Tierlists\RelationManagers\CommentsRelationManager;
use App\Models\Comment;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

describe('tier list comments', function () {
    test('lists the comments left on that tier list', function () {
        $tierlist = publicCompletedTierlist();
        $other = publicCompletedTierlist();

        $comment = $tierlist->comment('Great tiers!', User::factory()->createOne());
        $elsewhere = $other->comment('Great tiers!', User::factory()->createOne());

        Livewire::actingAs(kyle())
            ->test(CommentsRelationManager::class, ['ownerRecord' => $tierlist, 'pageClass' => ViewTierlist::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$comment])
            ->assertCanNotSeeTableRecords([$elsewhere]);
    });

    test('lets the admin delete one', function () {
        $tierlist = publicCompletedTierlist();
        $comment = $tierlist->comment('Great!', User::factory()->createOne());

        Livewire::actingAs(kyle())
            ->test(CommentsRelationManager::class, ['ownerRecord' => $tierlist, 'pageClass' => EditTierlist::class])
            ->callAction(TestAction::make(DeleteAction::class)->table($comment));

        expect(Comment::query()->find($comment->getKey()))->toBeNull();
    });
});
