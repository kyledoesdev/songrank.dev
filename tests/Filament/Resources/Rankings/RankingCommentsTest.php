<?php

use App\Filament\Resources\Rankings\Pages\EditRanking;
use App\Filament\Resources\Rankings\Pages\ViewRanking;
use App\Filament\Resources\Rankings\RelationManagers\CommentsRelationManager;
use App\Models\Comment;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

describe('ranking comments', function () {
    test('lists the comments left on that ranking', function () {
        $ranking = publicCompletedRanking();
        $other = publicCompletedRanking();

        $comment = $ranking->comment('Great list!', User::factory()->createOne());
        $elsewhere = $other->comment('Great list!', User::factory()->createOne());

        Livewire::actingAs(kyle())
            ->test(CommentsRelationManager::class, ['ownerRecord' => $ranking, 'pageClass' => ViewRanking::class])
            ->assertOk()
            ->assertCanSeeTableRecords([$comment])
            ->assertCanNotSeeTableRecords([$elsewhere]);
    });

    test('lets the admin delete one', function () {
        $ranking = publicCompletedRanking();
        $comment = $ranking->comment('Great!', User::factory()->createOne());

        Livewire::actingAs(kyle())
            ->test(CommentsRelationManager::class, ['ownerRecord' => $ranking, 'pageClass' => EditRanking::class])
            ->callAction(TestAction::make(DeleteAction::class)->table($comment));

        expect(Comment::query()->find($comment->getKey()))->toBeNull();
    });
});
