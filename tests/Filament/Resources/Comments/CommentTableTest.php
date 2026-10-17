<?php

use App\Filament\Resources\Comments\Pages\ListComments;
use App\Models\Comment;
use App\Models\Review;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(kyle()));

describe('finding a comment', function () {
    it('searches what was written', function () {
        $ranking = publicCompletedRanking();
        $wanted = $ranking->comment('the bridge on track four', User::factory()->createOne());
        $other = $ranking->comment('great list', User::factory()->createOne());

        Livewire::test(ListComments::class)
            ->searchTable('bridge on track')
            ->assertCanSeeTableRecords([Comment::find($wanted->getKey())])
            ->assertCanNotSeeTableRecords([Comment::find($other->getKey())]);
    });

    it('searches who wrote it', function () {
        $ranking = publicCompletedRanking();
        $wanted = $ranking->comment('great list', User::factory()->createOne(['name' => 'Findable Commenter']));
        $other = $ranking->comment('great list', User::factory()->createOne());

        Livewire::test(ListComments::class)
            ->searchTable('Findable Commenter')
            ->assertCanSeeTableRecords([Comment::find($wanted->getKey())])
            ->assertCanNotSeeTableRecords([Comment::find($other->getKey())]);
    });

    it('filters by the kind of record commented on', function () {
        $onReview = publicPublishedReview()->comment('nice review', User::factory()->createOne());
        $onRanking = publicCompletedRanking()->comment('nice list', User::factory()->createOne());

        Livewire::test(ListComments::class)
            ->filterTable('commentable_type', Review::class)
            ->assertCanSeeTableRecords([Comment::find($onReview->getKey())])
            ->assertCanNotSeeTableRecords([Comment::find($onRanking->getKey())]);
    });
});

describe('viewing a comment on the site', function () {
    it('links a comment to its place on the record', function () {
        $tierlist = publicCompletedTierlist();
        $comment = Comment::find($tierlist->comment('great tiers', User::factory()->createOne())->getKey());

        Livewire::test(ListComments::class)
            ->assertActionHasUrl(TestAction::make('viewOnSite')->table($comment), $tierlist->url()."#comment-{$comment->getKey()}");
    });

    it('links a reply to the record its thread is on', function () {
        $ranking = publicCompletedRanking();
        $parent = $ranking->comment('great list', $ranking->user);
        $reply = Comment::find($parent->comment('thanks!', User::factory()->createOne())->getKey());

        Livewire::test(ListComments::class)
            ->assertActionHasUrl(TestAction::make('viewOnSite')->table($reply), $ranking->url()."#comment-{$reply->getKey()}");
    });
});
