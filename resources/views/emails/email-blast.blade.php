@component('mail::message')

@if ($notifiable && $notifiable->name)
    <p>
        Hey {{ $notifiable->name }}!
    </p>
@endif

{!! Str::of($emailTemplate->content)->markdown() !!}

@component('mail::button', ['url' => config('app.url')])
    Login to {{ config('app.name') }}
@endcomponent

Thanks for using Song Rank!<br>
{{ config('app.name') }}
@endcomponent