<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Music Rankings Newsletter</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif; line-height: 1.6; color: #374151; background-color: #f9fafb; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 32px;">
        <div style="text-align: center; margin-bottom: 32px;">
            <h1 style="font-size: 36px; font-weight: bold; color: #111827; margin: 0 0 8px 0;">{{ config('app.name') }}</h1>
        </div>
        <div style="text-align: center; margin-bottom: 32px;">
            <h1 style="font-size: 24px; font-weight: bold; color: #111827; margin: 0 0 8px 0;">{{ now()->subMonth()->format('F') }} - {{ now()->format('F Y') }} Newsletter</h1>
            <p style="color: #6b7280; margin: 0; font-size: 16px;">Discover what artists & playlists the community has been ranking this month! Check out a selection of the rankings completed this past month from fellow music lovers.</p>
        </div>
        
        <div style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 24px 0; text-align: center;">
            <strong style="color: #374151;">{{ $rankingsCount }}</strong> new rankings completed last month.
        </div>
        
        {!! $html !!}
        
        <div style="background-color: #f3f4f6; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 24px 0; text-align: center;">
            Keep creating and sharing your music rankings! The community loves discovering new songs through your lists.
        </div>
        
        <div style="text-align: center; margin: 32px 0;">
            <a href="{{ route('welcome') }}" style="display: inline-block; background-color: #3b82f6; color: #ffffff; font-weight: 500; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-size: 16px;">
                Create Your Own Ranking
            </a>
        </div>

        <div style="margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
            <p style="margin: 0 0 16px 0; color: #374151;">
                Thanks for using {{ config('app.name') }}!<br>
                {{ config('app.name') }}
            </p>
            
            <p style="font-size: 14px; color: #6b7280; margin: 0;">
                You're receiving this because you have newsletter emails enabled in your preferences. 
                You can <a href="{{ route('settings') }}" style="color: #3b82f6; text-decoration: none;">update your email preferences</a> anytime.
            </p>
        </div>
    </div>
    
</body>
</html>