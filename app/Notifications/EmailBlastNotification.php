<?php

namespace App\Notifications;

use App\Models\EmailTemplate;
use App\Notifications\Concerns\OffersNewsletterUnsubscribe;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailBlastNotification extends Notification implements ShouldQueue
{
    use OffersNewsletterUnsubscribe;
    use Queueable;

    public function __construct(private EmailTemplate $emailTemplate) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->emailTemplate->subject)
            ->markdown('emails.email-blast', [
                'notifiable' => $notifiable,
                'emailTemplate' => $this->emailTemplate,
            ])
            ->withSymfonyMessage($this->listUnsubscribeHeaders($notifiable));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
