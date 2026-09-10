<?php

namespace App\Notifications;

use App\Models\ProLicense;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProPurchaseReceipt extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private ProLicense $license) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Stripe sends its own payment receipt; this one welcomes them to Pro and
     * points at what they just unlocked.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(config('app.name').' - Welcome to Pro')
            ->markdown('emails.pro-purchase-receipt', [
                'notifiable' => $notifiable,
                'license' => $this->license,
                'billingUrl' => route('billing'),
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Welcome to Song Rank Pro! Your license is active.',
            'url' => route('billing'),
            'entity' => [
                'type' => 'pro_license',
                'uuid' => $this->license->uuid,
                'name' => 'Song Rank Pro',
            ],
        ];
    }
}
