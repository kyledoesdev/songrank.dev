<?php

use App\Models\Ranking;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Spatie\Comments\Enums\NotificationSubscriptionType;
use Spatie\Comments\Notifications\ApprovedCommentNotification;

use function Pest\Laravel\freezeTime;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

beforeEach(function () {
    Notification::fake();
});

describe('an email about a new comment', function () {
    it('links to stop the emails about that record', function () {
        freezeTime();

        [$ranking, $subscriber] = rankingWithCommentSubscriber();

        $ranking->comment('Great list!', User::factory()->createOne());

        Notification::assertSentTo(
            $subscriber,
            ApprovedCommentNotification::class,
            fn (ApprovedCommentNotification $notification) => str_contains(
                $notification->toMail()->render(),
                e($subscriber->unsubscribeFromCommentNotificationsUrl($ranking)),
            ),
        );
    });
});

describe('unsubscribing from the link', function () {
    it('stops the emails about that record', function () {
        [$ranking, $subscriber] = rankingWithCommentSubscriber();
        $link = $subscriber->unsubscribeFromCommentNotificationsUrl($ranking);

        get($link)->assertOk();
        post($link)->assertOk();

        $ranking->comment('Great list!', User::factory()->createOne());

        Notification::assertNotSentTo($subscriber, ApprovedCommentNotification::class);
    });

    it('will not take a link pointed at somebody else', function () {
        [$ranking, $subscriber] = rankingWithCommentSubscriber();
        $other = User::factory()->createOne()->subscribeToCommentNotifications($ranking, NotificationSubscriptionType::All);

        $link = str_replace(
            "/comment-subscription/{$subscriber->subscriberNotificationSubscriptions()->sole()->getKey()}/",
            "/comment-subscription/{$other->subscriberNotificationSubscriptions()->sole()->getKey()}/",
            $subscriber->unsubscribeFromCommentNotificationsUrl($ranking),
        );

        get($link)->assertForbidden();
        post($link)->assertForbidden();

        expect($other->notificationSubscriptionType($ranking))->toBe(NotificationSubscriptionType::All);
    });
});

/**
 * A public ranking, and a user who asked to hear about every comment on it.
 *
 * @return array{0: Ranking, 1: User}
 */
function rankingWithCommentSubscriber(): array
{
    $ranking = publicCompletedRanking(attributes: ['comments_enabled' => true]);
    $subscriber = User::factory()->createOne();

    $subscriber->subscribeToCommentNotifications($ranking, NotificationSubscriptionType::All);

    return [$ranking, $subscriber];
}
