<?php

use App\Jobs\DeleteUserJob;
use App\Livewire\Explorer\ReviewsFeed;
use App\Livewire\Explorer\TierlistsFeed;
use App\Livewire\Profile\Settings;
use App\Models\Ranking;
use App\Models\Review;
use App\Models\Tierlist;
use App\Models\User;
use App\Notifications\DownloadDataNotification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mime\Part\DataPart;

use function Pest\Laravel\assertGuest;

describe('newsletter preferences', function () {
    test('user can enable newsletter emails', function () {
        $user = User::factory()->createOne();
        $user->preferences->update(['recieve_newsletter_emails' => false]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('updateSetting', 'recieve_newsletter_emails', true);

        expect($user->fresh()->preferences->recieve_newsletter_emails)->toBeTrue();
    });

    test('user can disable newsletter emails', function () {
        $user = User::factory()->createOne();
        $user->preferences->update(['recieve_newsletter_emails' => true]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('updateSetting', 'recieve_newsletter_emails', false);

        expect($user->fresh()->preferences->recieve_newsletter_emails)->toBeFalse();
    });
});

describe('which preferences the page can change', function () {
    test('saves each preference it offers', function (string $preference) {
        $user = User::factory()->createOne();
        $user->preferences->update([$preference => false]);

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('updateSetting', $preference, true);

        expect($user->fresh()->preferences->{$preference})->toBeTrue();
    })->with(['recieve_newsletter_emails', 'catalog_is_public']);

    test('refuses any other column, leaving the row as it was', function (string $column, mixed $value) {
        $user = User::factory()->createOne();
        $before = $user->preferences()->first()->getAttributes();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('updateSetting', $column, $value)
            ->assertForbidden();

        expect($user->preferences()->first()->getAttributes())->toBe($before);
    })->with([
        'handing the row to another user' => ['user_id', 999],
        'trashing the row' => ['deleted_at', '2026-01-01 00:00:00'],
        'a preference set somewhere else' => ['catalog_sort', 'custom'],
        'a column that is not a preference' => ['is_dev', true],
    ]);
});

describe('account deletion', function () {
    test('user receives download notification when deleting account with rankings', function () {
        Notification::fake();

        $user = User::factory()->createOne();
        Ranking::factory()->count(3)->create(['user_id' => $user->getKey()]);

        DeleteUserJob::dispatch($user);

        Notification::assertSentTo($user, DownloadDataNotification::class);
    });

    test('mails the export while its songs still exist, rather than queueing it for later', function () {
        Queue::fake();

        $user = User::factory()->createOne();
        $ranking = Ranking::factory()->for($user)->createOne();

        (new DeleteUserJob($user))->handle();

        $export = mailedRankingsExport();

        expect($export->getSheetCount())->toBe(1)
            ->and($export->getSheet(0)->getHighestRow())->toBe($ranking->songs()->withTrashed()->count() + 1);
    });

    test('user does not receive download notification when deleting account without rankings', function () {
        Notification::fake();

        $user = User::factory()->createOne();

        DeleteUserJob::dispatch($user);

        Notification::assertNotSentTo($user, DownloadDataNotification::class);
    });

    test('user can delete their account', function () {
        Notification::fake();

        $user = User::factory()->createOne();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('destroy', $user->getKey())
            ->assertRedirect('/');

        expect(User::find($user->getKey()))->toBeNull();
        assertGuest();
    });

    test('account deletion also deletes their rankings', function () {
        Notification::fake();

        $user = User::factory()->createOne();
        $rankingIds = Ranking::factory()->count(3)->create(['user_id' => $user->getKey()])->pluck('id');

        DeleteUserJob::dispatchSync($user);

        expect(User::find($user->getKey()))->toBeNull();
        expect(Ranking::whereIn('id', $rankingIds)->count())->toBe(0);
    });

    test('account deletion also deletes their tier lists and reviews', function () {
        $user = User::factory()->createOne();
        $tierlist = publicCompletedTierlist(['user_id' => $user->getKey()]);
        $review = Review::factory()->for($user)->published()->public()->createOne();

        DeleteUserJob::dispatchSync($user);

        expect(Tierlist::find($tierlist->getKey()))->toBeNull();
        expect(Review::find($review->getKey()))->toBeNull();
    });

    test('a deleted author no longer breaks the explore feeds', function () {
        $tierlist = publicCompletedTierlist();
        $review = Review::factory()->published()->public()->createOne();

        DeleteUserJob::dispatchSync($tierlist->user);
        DeleteUserJob::dispatchSync($review->user);

        $tierlists = Livewire::test(TierlistsFeed::class)->assertOk()->instance()->tierlists;
        $reviews = Livewire::test(ReviewsFeed::class)->assertOk()->instance()->reviews;

        expect($tierlists)->toBeEmpty()
            ->and($reviews)->toBeEmpty();
    });

    test('account deletion erases spotify tokens and technical data', function () {
        Notification::fake();

        $user = User::factory()->createOne([
            'external_token' => 'access-token',
            'external_refresh_token' => 'refresh-token',
            'ip_address' => '203.0.113.10',
            'user_agent' => 'Mozilla/5.0',
            'user_platform' => 'Windows',
            'user_packet' => ['city' => 'Philadelphia', 'zip' => '00000'],
        ]);

        DeleteUserJob::dispatchSync($user);

        $deleted = User::withTrashed()->find($user->getKey());

        expect($deleted->external_token)->toBeNull()
            ->and($deleted->external_refresh_token)->toBeNull()
            ->and($deleted->ip_address)->toBeNull()
            ->and($deleted->user_agent)->toBeNull()
            ->and($deleted->user_platform)->toBeNull()
            ->and($deleted->user_packet)->toBeNull();
    });

    test('account deletion keeps the identity needed to restore a returning user', function () {
        Notification::fake();

        $user = User::factory()->createOne([
            'spotify_id' => 'spotify-abc',
            'name' => 'Returning User',
            'email' => 'returning@example.com',
        ]);

        DeleteUserJob::dispatchSync($user);

        $deleted = User::withTrashed()->find($user->getKey());

        expect($deleted)->not->toBeNull()
            ->and($deleted->trashed())->toBeTrue()
            ->and($deleted->spotify_id)->toBe('spotify-abc')
            ->and($deleted->name)->toBe('Returning User')
            ->and($deleted->email)->toBe('returning@example.com');
    });

    test('user cannot delete another users account', function () {
        $user = User::factory()->createOne();
        $otherUser = User::factory()->createOne();

        Livewire::actingAs($user)
            ->test(Settings::class)
            ->call('destroy', $otherUser->getKey())
            ->assertForbidden();

        expect(User::find($otherUser->getKey()))->not->toBeNull();
    });
});

/**
 * The rankings spreadsheet attached to whatever mail has been sent so far.
 */
function mailedRankingsExport(): Spreadsheet
{
    $attachment = Mail::getSymfonyTransport()->messages()
        ->flatMap(fn (SentMessage $message) => $message->getOriginalMessage()->getAttachments())
        ->sole(fn (DataPart $part) => $part->getFilename() === 'rankings.xlsx');

    $path = tempnam(sys_get_temp_dir(), 'export');
    file_put_contents($path, $attachment->getBody());

    $spreadsheet = IOFactory::createReader('Xlsx')->load($path);

    unlink($path);

    return $spreadsheet;
}
