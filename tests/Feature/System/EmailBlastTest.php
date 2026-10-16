<?php

use App\Jobs\SendEmailJob;
use App\Models\EmailTemplate;
use App\Notifications\EmailBlastNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

describe('an email blast', function () {
    it('goes to everyone who opted in, under the template\'s subject', function () {
        $template = blastTemplate();
        $reader = newsletterSubscriber();
        $optedOut = newsletterSubscriber(subscribed: false);

        (new SendEmailJob($template))->handle();

        Notification::assertSentTo(
            $reader,
            EmailBlastNotification::class,
            fn (EmailBlastNotification $blast) => $blast->toMail($reader)->subject === $template->subject,
        );
        Notification::assertNotSentTo($optedOut, EmailBlastNotification::class);
    });

    it('renders the template as markdown', function () {
        $template = blastTemplate(['content' => 'Tier lists, reviews and **the catalog**.']);
        $reader = newsletterSubscriber();

        (new SendEmailJob($template))->handle();

        Notification::assertSentTo(
            $reader,
            EmailBlastNotification::class,
            fn (EmailBlastNotification $blast) => preg_match('/<strong[^>]*>the catalog<\/strong>/', (string) $blast->toMail($reader)->render()) === 1,
        );
    });
});

function blastTemplate(array $attributes = []): EmailTemplate
{
    return EmailTemplate::create([
        'name' => 'Pro launch',
        'subject' => 'Song Rank Pro is here',
        'content' => 'Tier lists, reviews and the catalog.',
        ...$attributes,
    ]);
}
