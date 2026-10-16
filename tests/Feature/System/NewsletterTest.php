<?php

use App\Models\Ranking;
use App\Notifications\Newsletter;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\artisan;

beforeEach(function () {
    Notification::fake();
});

describe('the monthly newsletter', function () {
    it('goes to everyone who opted in, and nobody who opted out', function () {
        $reader = newsletterSubscriber();
        $optedOut = newsletterSubscriber(subscribed: false);
        publicCompletedRanking();

        artisan('newsletter:send')->assertSuccessful();

        Notification::assertSentTo($reader, Newsletter::class);
        Notification::assertNotSentTo($optedOut, Newsletter::class);
    });

    it('features only the public rankings finished this past month', function () {
        $reader = newsletterSubscriber();
        $current = publicCompletedRanking();

        Ranking::factory()->create(['is_public' => false, 'is_ranked' => true, 'completed_at' => now()]);
        Ranking::factory()->create(['is_public' => true]);
        publicCompletedRanking(attributes: ['completed_at' => now()->subMonths(2)]);

        artisan('newsletter:send')->assertSuccessful();

        Notification::assertSentTo($reader, Newsletter::class, function (Newsletter $newsletter) use ($reader, $current): bool {
            $html = (string) $newsletter->toMail($reader)->render();

            return Ranking::all()->every(
                fn (Ranking $ranking) => str_contains($html, 'href="'.route('ranking', $ranking->getKey()).'"') === $ranking->is($current)
            );
        });
    });

    it('stays quiet when nothing was finished this past month', function () {
        newsletterSubscriber();
        publicCompletedRanking(attributes: ['completed_at' => now()->subMonths(2)]);

        artisan('newsletter:send')->assertSuccessful();

        Notification::assertNothingSent();
    });
});
