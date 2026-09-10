<?php

namespace App\Http\Controllers\Billing;

use App\Actions\Billing\FulfillProPurchase;
use App\Actions\Billing\StartProCheckout;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Checkout;
use Stripe\Exception\ApiErrorException;

/**
 * The checkout session, as a resource.
 *
 * `show` and `destroy` answer GET because Stripe redirects the customer's
 * browser to them; the verbs describe what happens to the session, not how it
 * is reached.
 */
class ProCheckoutController extends Controller
{
    public function store(StartProCheckout $startCheckout): Checkout
    {
        return $startCheckout->handle(Auth::user());
    }

    /**
     * Handle the return trip from a completed checkout.
     *
     * The webhook is the authority; this only exists so the customer does not
     * wait on delivery to see their purchase. The session id arrives in the
     * query string and is therefore untrusted — the session is re-read from
     * Stripe and its payment status checked before anything is granted.
     */
    public function show(FulfillProPurchase $fulfill): RedirectResponse
    {
        $sessionId = request()->string('session_id')->toString();

        if (blank($sessionId)) {
            return redirect()->route('billing');
        }

        try {
            $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId);
        } catch (ApiErrorException) {
            /* The webhook will still land; never show an error over a timing issue. */
            return redirect()
                ->route('billing')
                ->with('success', 'Thanks! Your purchase is being confirmed and will appear here shortly.');
        }

        /*
         * Null means the session was not paid, or referenced a license we do
         * not know — an async payment still clearing, or a stale session id
         * somebody pasted. Neither is a welcome.
         */
        if (is_null($fulfill->handle($session->toArray()))) {
            return redirect()
                ->route('billing')
                ->with('success', 'Thanks! Your purchase is being confirmed and will appear here shortly.');
        }

        return redirect()
            ->route('billing')
            ->with('success', 'Welcome to Song Rank Pro!');
    }

    /**
     * The customer backed out of the checkout.
     */
    public function cancel(): RedirectResponse
    {
        return redirect()
            ->route('billing')
            ->with('success', 'Your payment session was cancelled. Nothing was charged.');
    }
}
