@component('mail::message')

@if ($notifiable && $notifiable->name)
    <p>
        Hi {{ $notifiable->name }},
    </p>
@endif

<p>
    Thanks for going Pro. Your license is active on your account right now — nothing else to do.
</p>

@component('mail::button', ['url' => $billingUrl])
View your billing details
@endcomponent

<p>
    Your invoice is on your billing page, and Stripe has emailed you a payment receipt separately.
</p>

Thanks for supporting SongRank!<br>
{{ config('app.name') }}
@endcomponent
