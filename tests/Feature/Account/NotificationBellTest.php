<?php

use App\Livewire\Notifications\Bell;
use App\Livewire\Notifications\Details;
use App\Models\Comment;
use App\Models\Ranking;
use App\Models\User;
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
});

function commentableRanking(): Ranking
{
    return publicCompletedRanking(attributes: ['comments_enabled' => true]);
}

function createComment(User $commenter, Ranking $ranking, string $text = 'Great ranking!'): Comment
{
    return Comment::create([
        'commentable_type' => Ranking::class,
        'commentable_id' => $ranking->getKey(),
        'commentator_type' => User::class,
        'commentator_id' => $commenter->getKey(),
        'original_text' => $text,
        'text' => $text,
        'approved_at' => now(),
    ]);
}
