<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use Closure;
use Symfony\Component\Mime\Email;

trait OffersNewsletterUnsubscribe
{
    /**
     * Lets mail clients show their own unsubscribe button and act on it in
     * one click, without the confirmation page (RFC 8058).
     */
    protected function listUnsubscribeHeaders(User $user): Closure
    {
        $url = $user->newsletterUnsubscribeUrl();

        return fn (Email $email) => $email->getHeaders()
            ->addTextHeader('List-Unsubscribe', "<{$url}>")
            ->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
    }
}
