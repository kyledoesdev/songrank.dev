<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class NewsletterUnsubscribeController extends Controller
{
    /**
     * Mail scanners open every link in an email, so following one only asks.
     */
    public function create(User $user): View
    {
        return view('emails.unsubscribe', ['unsubscribeUrl' => $user->newsletterUnsubscribeUrl()]);
    }

    /**
     * Both the confirmation form and a mail client's one-click unsubscribe
     * (RFC 8058) post here, the latter with no CSRF token.
     */
    public function store(User $user): View
    {
        $user->preferences()->update(['recieve_newsletter_emails' => false]);

        return view('emails.unsubscribe', ['unsubscribeUrl' => null]);
    }
}
