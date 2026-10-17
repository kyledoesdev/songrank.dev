<?php

use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Details;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\Ranking;
use App\Models\User;
use App\Notifications\NewComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

describe('notification bell component', function () {
    test('renders for authenticated users', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Bell::class)
            ->assertOk();
    });

    test('shows zero unread count when no notifications exist', function () {
        Livewire::actingAs(User::factory()->createOne())
            ->test(Bell::class)
            ->assertSet('unreadCount', 0);
    });

    test('shows correct unread count', function () {
        $ranking = commentableRanking();
        $commenter = User::factory()->createOne();

        createComment($commenter, $ranking, 'First comment');
        createComment($commenter, $ranking, 'Second comment');

        Livewire::actingAs($ranking->user)
            ->test(Bell::class)
            ->assertSet('unreadCount', 2);
    });

    test('opening bell marks all notifications as read', function () {
        $ranking = commentableRanking();
        $owner = $ranking->user;
        $commenter = User::factory()->createOne();

        createComment($commenter, $ranking, 'Comment 1');
        createComment($commenter, $ranking, 'Comment 2');
        createComment($commenter, $ranking, 'Comment 3');

        Livewire::actingAs($owner)
            ->test(Bell::class)
            ->assertSet('unreadCount', 3)
            ->call('open')
            ->assertSet('unreadCount', 0);

        expect($owner->unreadNotifications()->count())->toBe(0);
        expect($owner->notifications()->count())->toBe(3);
    });
});

describe('opening one notification', function () {
    test('shows the viewer one of their own', function () {
        $ranking = commentableRanking();
        createComment(User::factory()->createOne(), $ranking);
        $notification = $ranking->user->notifications()->sole();

        $component = Livewire::actingAs($ranking->user)
            ->test(Details::class)
            ->call('show', $notification->getKey())
            ->assertSet('open', true);

        expect($component->instance()->notification->is($notification))->toBeTrue();
    });

    test('will not open somebody else\'s', function () {
        $ranking = commentableRanking();
        createComment(User::factory()->createOne(), $ranking);
        $notification = $ranking->user->notifications()->sole();

        Livewire::actingAs(User::factory()->createOne())
            ->test(Details::class)
            ->call('show', $notification->getKey())
            ->assertNotFound()
            ->assertSet('open', false);
    });
});

describe('notifications page', function () {
    test('authenticated user can view notifications page', function () {
        actingAs(User::factory()->createOne())
            ->get(route('notifications'))
            ->assertOk();
    });
});

describe('comment notification dispatch', function () {
    test('notification is created when someone comments on a ranking', function () {
        $ranking = commentableRanking();
        $owner = $ranking->user;
        $commenter = User::factory()->createOne();

        createComment($commenter, $ranking, 'This is a test comment');

        expect($owner->notifications()->count())->toBe(1);

        $notification = $owner->notifications()->first();

        expect($notification->user_name)->toBe($commenter->name);
        expect($notification->entity['name'])->toBe($ranking->name);
        expect($notification->entity['id'])->toBe($ranking->getKey());
    });

    test('notification is created when someone comments on a tier list or a review', function (Closure $record) {
        $commentable = $record();
        $commenter = User::factory()->createOne();

        $comment = createComment($commenter, $commentable);

        $notification = $commentable->user->notifications()->sole();

        expect($notification->type)->toBe(NewComment::class)
            ->and($notification->entity['id'])->toBe($commentable->getKey())
            ->and($notification->actionUrl())->toBe($commentable->url()."#comment-{$comment->getKey()}");
    })->with([
        'tier list' => fn () => publicCompletedTierlist(['comments_enabled' => true]),
        'review' => fn () => publicPublishedReview(['comments_enabled' => true]),
    ]);

    test('notification is not created when owner comments on their own ranking', function () {
        $ranking = commentableRanking();

        createComment($ranking->user, $ranking, 'My own comment');

        expect($ranking->user->notifications()->count())->toBe(0);
    });

    test('unread notification is removed when comment is deleted', function () {
        $ranking = commentableRanking();
        $commenter = User::factory()->createOne();

        $comment = createComment($commenter, $ranking);

        expect($ranking->user->notifications()->count())->toBe(1);

        $comment->delete();

        expect($ranking->user->notifications()->count())->toBe(0);
    });

    test('read notification is kept when comment is deleted', function () {
        $ranking = commentableRanking();
        $commenter = User::factory()->createOne();

        $comment = createComment($commenter, $ranking);

        $ranking->user->notifications()->first()->markAsRead();

        $comment->delete();

        expect($ranking->user->notifications()->count())->toBe(1);
    });

    test('notifications from before tier lists and reviews still read as comments', function () {
        $owner = User::factory()->createOne();

        DB::table('notifications')->insert([
            'id' => Str::uuid()->toString(),
            'type' => 'App\Notifications\NewCommentOnRanking',
            'notifiable_type' => User::class,
            'notifiable_id' => $owner->getKey(),
            'data' => json_encode(['comment_id' => 1]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        (require database_path('migrations/2026_10_16_202901_rename_ranking_comment_notifications.php'))->up();

        expect(Notification::query()->sole()->isCommentNotification())->toBeTrue();
    });
});

function commentableRanking(): Ranking
{
    return publicCompletedRanking(attributes: ['comments_enabled' => true]);
}

function createComment(User $commenter, Model $commentable, string $text = 'Great ranking!'): Comment
{
    return Comment::create([
        'commentable_type' => $commentable::class,
        'commentable_id' => $commentable->getKey(),
        'commentator_type' => User::class,
        'commentator_id' => $commenter->getKey(),
        'original_text' => $text,
        'text' => $text,
        'approved_at' => now(),
    ]);
}
