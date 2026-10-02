@php
    $badgeColors = match($ranking->type) {
        \App\Enums\RankingType::ARTIST => ['bg' => '#f3e8ff', 'text' => '#7e22ce'],
        \App\Enums\RankingType::PLAYLIST => ['bg' => '#dcfce7', 'text' => '#15803d'],
        \App\Enums\RankingType::SHOW => ['bg' => '#dbeafe', 'text' => '#1d4ed8'],
    };
@endphp

<div style="background-color: white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); border-radius: 12px; margin: 1rem 0; padding: 16px; border: 1px solid #e5e7eb;">
    <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse;">
        <tr>
            {{-- Cover + Spotify Logo --}}
            <td width="112" style="vertical-align: top; padding-right: 16px;">
                <img
                    src="{{ $ranking->source->cover() }}"
                    width="112"
                    height="112"
                    style="border-radius: 12px; border: 1px solid #e4e4e7; display: block; object-fit: cover;"
                    alt="{{ $ranking->source->name() }}"
                >
                <div style="margin-top: 8px;">
                    <a
                        href="{{ $ranking->source->spotifyUrl() }}"
                        target="_blank"
                        style="display: inline-flex; align-items: center; gap: 4px; border-bottom: 2px solid #06D6A0; padding-bottom: 2px; text-decoration: none; color: #06D6A0; font-size: 12px;"
                    >
                        🎵 Spotify ↗
                    </a>
                </div>
            </td>

            {{-- Content --}}
            <td style="vertical-align: top;">
                {{-- Title + Badge --}}
                <div style="margin-bottom: 4px;">
                    <span style="font-size: 18px; font-weight: bold; color: #1f2937;">{{ Str::limit($ranking->name, 35) }}</span>
                    <span style="display: inline-block; font-size: 10px; padding: 2px 8px; border-radius: 9999px; background-color: {{ $badgeColors['bg'] }}; color: {{ $badgeColors['text'] }}; margin-left: 6px; vertical-align: middle;">
                        {{ $ranking->type->label() }}
                    </span>
                </div>

                {{-- Source Name --}}
                <div style="font-size: 14px; color: #71717a; margin-bottom: 12px;">
                    {{ $ranking->source->name() }}
                </div>

                {{-- Stats Pills --}}
                <div style="margin-bottom: 12px;">
                    @if($ranking->songs && $ranking->songs->isNotEmpty())
                        <span style="display: inline-block; font-size: 12px; background-color: #f4f4f5; color: #52525b; padding: 4px 10px; border-radius: 8px; margin-right: 6px; margin-bottom: 4px;">
                            ⭐ {{ Str::limit($ranking->songs[0]->title, 20) }}
                        </span>
                    @endif

                    <span style="display: inline-block; font-size: 12px; background-color: #f4f4f5; color: #52525b; padding: 4px 10px; border-radius: 8px; margin-right: 6px; margin-bottom: 4px;">
                        # {{ count($ranking->songs) }} {{ $ranking->type->itemLabel() }}
                    </span>

                    @unless ($ranking->is_ranked)
                        <span style="display: inline-block; font-size: 12px; background-color: #fffbeb; color: #a16207; padding: 4px 10px; border-radius: 8px; margin-bottom: 4px;">
                            In Progress
                        </span>
                    @endunless
                </div>

                {{-- Footer: User + Time --}}
                <div style="font-size: 12px; color: #a1a1aa; margin-bottom: 12px;">
                    👤 <a href="{{ route('profile', ['id' => $ranking->user->spotify_id]) }}" target="_blank" style="color: #a1a1aa; text-decoration: none;">{{ $ranking->user->name }}</a>
                    &nbsp;|&nbsp;
                    🕒 {{ Carbon\Carbon::parse($ranking->completed_at)->toFormattedDateString() }}
                </div>

                {{-- CTA Button --}}
                <a
                    href="{{ route('ranking', $ranking->getKey()) }}"
                    style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 14px; font-weight: 500;"
                >
                    View Ranking →
                </a>
            </td>
        </tr>
    </table>
</div>
