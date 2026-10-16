<?php

use App\Models\EmailTemplate;
use App\Models\User;
use App\Notifications\EmailBlastNotification;
use App\Notifications\Newsletter;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Mime\Email;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

describe('the unsubscribe link', function () {
    it('is in the newsletter, footer and headers both', function () {
        $reader = newsletterSubscriber();

        $reader->notify(new Newsletter(collect([publicCompletedRanking()])));

        expectOneClickUnsubscribe(sentNewsletterEmail(), $reader);
    });

    it('is in an email blast, footer and headers both', function () {
        $reader = newsletterSubscriber();
        $template = EmailTemplate::create(['name' => 'Launch', 'subject' => 'Launch', 'content' => 'Launch']);

        $reader->notify(new EmailBlastNotification($template));

        expectOneClickUnsubscribe(sentNewsletterEmail(), $reader);
    });
});

describe('unsubscribing', function () {
    it('only asks when the link is opened, because mail scanners open links too', function () {
        $reader = newsletterSubscriber();
        $url = $reader->newsletterUnsubscribeUrl();

        get($url)
            ->assertOk()
            ->assertSee('action="'.$url.'"', false);

        expect(receivesNewsletter($reader))->toBeTrue();
    });

    it('unsubscribes once confirmed', function () {
        $reader = newsletterSubscriber();
        $neighbour = newsletterSubscriber();

        post($reader->newsletterUnsubscribeUrl())->assertOk();

        expect(receivesNewsletter($reader))->toBeFalse()
            ->and(receivesNewsletter($neighbour))->toBeTrue();
    });

    it('takes a one-click unsubscribe from a mail client, which sends no CSRF token', function () {
        Route::post('/csrf-probe', fn () => 'ok')->middleware('web');
        app()->detectEnvironment(fn (): string => 'production');

        $reader = newsletterSubscriber();

        post('/csrf-probe')->assertStatus(419);
        post($reader->newsletterUnsubscribeUrl(), ['List-Unsubscribe' => 'One-Click'])->assertOk();

        expect(receivesNewsletter($reader))->toBeFalse();
    });

    it('is harmless to follow twice', function () {
        $reader = newsletterSubscriber();

        post($reader->newsletterUnsubscribeUrl())->assertOk();
        post($reader->newsletterUnsubscribeUrl())->assertOk();

        expect(receivesNewsletter($reader))->toBeFalse();
    });

    it('refuses a tampered signature', function () {
        $reader = newsletterSubscriber();
        $tampered = $reader->newsletterUnsubscribeUrl().'0';

        get($tampered)->assertForbidden();
        post($tampered)->assertForbidden();

        expect(receivesNewsletter($reader))->toBeTrue();
    });

    it('cannot be pointed at another reader', function () {
        $reader = newsletterSubscriber();
        $neighbour = newsletterSubscriber();

        $forged = str_replace(
            "/newsletter/{$reader->getKey()}/",
            "/newsletter/{$neighbour->getKey()}/",
            $reader->newsletterUnsubscribeUrl(),
        );

        expect($forged)->not->toBe($reader->newsletterUnsubscribeUrl());

        post($forged)->assertForbidden();

        expect(receivesNewsletter($neighbour))->toBeTrue();
    });
});

function sentNewsletterEmail(): Email
{
    return app('mailer')->getSymfonyTransport()->messages()->sole()->getOriginalMessage();
}

function receivesNewsletter(User $user): bool
{
    return (bool) $user->preferences()->value('recieve_newsletter_emails');
}

function expectOneClickUnsubscribe(Email $email, User $reader): void
{
    $url = $reader->newsletterUnsubscribeUrl();

    expect($email->getHeaders()->getHeaderBody('List-Unsubscribe'))->toBe("<{$url}>")
        ->and($email->getHeaders()->getHeaderBody('List-Unsubscribe-Post'))->toBe('List-Unsubscribe=One-Click')
        ->and((string) $email->getHtmlBody())->toContain('href="'.$url.'"');
}
